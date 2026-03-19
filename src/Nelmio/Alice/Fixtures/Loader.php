<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Fixtures;

use Nelmio\Alice\Fixtures\Parser\Parser;
use Nelmio\Alice\Fixtures\Builder\Builder;
use Nelmio\Alice\Instances\Instantiator\Instantiator;
use Nelmio\Alice\Instances\Processor\Methods\MethodInterface as ProcessorMethodInterface;
use Nelmio\Alice\Instances\Processor\Methods\Parameterized;
use Nelmio\Alice\Instances\Processor\Methods\ArrayValue;
use Nelmio\Alice\Instances\Processor\Methods\Conditional;
use Nelmio\Alice\Instances\Processor\Methods\Reference;
use Nelmio\Alice\Instances\Processor\Methods\UnescapeAt;
use Nelmio\Alice\Fixtures\Parser\Methods\Php;
use Nelmio\Alice\Fixtures\Parser\Methods\Yaml;
use Nelmio\Alice\Fixtures\Parser\Methods\MethodInterface as ParserMethodInterface;
use Nelmio\Alice\Fixtures\Builder\Methods\ReferenceRangeName;
use Nelmio\Alice\Fixtures\Builder\Methods\RangeName;
use Nelmio\Alice\Fixtures\Builder\Methods\MethodInterface as BuilderMethodInterface;
use Nelmio\Alice\Fixtures\Builder\Methods\ListName;
use Nelmio\Alice\Fixtures\Builder\Methods\SimpleName;
use Nelmio\Alice\Instances\Instantiator\Methods\MethodInterface as InstantiatorMethodInterface;
use Nelmio\Alice\Instances\Instantiator\Methods\ReflectionWithoutConstructor;
use Nelmio\Alice\Instances\Instantiator\Methods\ReflectionWithConstructor;
use Nelmio\Alice\Instances\Instantiator\Methods\EmptyConstructor;
use Nelmio\Alice\Instances\Populator\Methods\ArrayAdd;
use Nelmio\Alice\Instances\Populator\Methods\Custom;
use Nelmio\Alice\Instances\Populator\Methods\ArrayDirect;
use Nelmio\Alice\Instances\Populator\Methods\Direct;
use Nelmio\Alice\Instances\Populator\Methods\Property;
use Nelmio\Alice\Instances\Populator\Methods\MagicCall;
use Nelmio\Alice\Instances\Populator\Methods\MethodInterface as PopulatorMethodInterface;
use Nelmio\Alice\Instances\Collection;
use Nelmio\Alice\Instances\Populator;
use Nelmio\Alice\Instances\Processor\Methods\Faker;
use Nelmio\Alice\Instances\Processor;
use Nelmio\Alice\Instances\Processor\Providers\IdentityProvider;
use Nelmio\Alice\PersisterInterface;
use Nelmio\Alice\Util\TypeHintChecker;
use Psr\Log\LoggerInterface;

/**
 * Loads fixtures from an array or file
 */
class Loader
{
    /**
     * @var Collection
     */
    protected $objects;

    /**
     * @var TypeHintChecker
     */
    protected $typeHintChecker;

    /**
     * @var Processor\Processor
     */
    protected $processor;

    /**
     * @var Parser\Parser
     **/
    protected $parser;

    /**
     * @var Builder\Builder
     */
    protected $builder;

    /**
     * @var Faker
     */
    protected $fakerProcessorMethod;

    /**
     * @var Instantiator\Instantiator
     */
    protected $instantiator;

    /**
     * @var Populator\Populator
     */
    protected $populator;

    /**
     * @var PersisterInterface
     */
    protected $manager;

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var callable|LoggerInterface
     */
    private $logger;

    /**
     * @param string $locale     default locale to use with faker if none is
     *                           specified in the expression
     * @param array  $providers  custom faker providers in addition to the default
     *                           ones from faker
     * @param int    $seed       a seed  to make sure faker generates data consistently across
     *                           runs, set to null to disable
     * @param array  $parameters create loader with default parameters
     */
    public function __construct($locale = 'en_US', array $providers = [], $seed = 1, array $parameters = [])
    {
        $this->objects         = new Collection;
        $this->typeHintChecker = new TypeHintChecker;
        $this->parameterBag    = new ParameterBag($parameters);

        $allProviders = array_merge($this->getBuiltInProviders(), $providers);

        $this->processor = new Processor\Processor(
            $this->objects,
            $this->getBuiltInProcessors($allProviders, $locale)
        );

        $this->parser = new Parser(
            $this->getBuiltInParsers()
        );

        $this->builder = new Builder(
            $this->getBuiltInBuilders()
        );

        $this->instantiator = new Instantiator(
            $this->getBuiltInInstantiators($this->processor, $this->typeHintChecker)
        );

        $this->populator = new Populator\Populator(
            $this->objects,
            $this->processor,
            $this->getBuiltInPopulators($this->typeHintChecker)
        );

        if (is_numeric($seed)) {
            mt_srand($seed);
        }
    }

    /**
     * Loads a fixture file
     *
     * @param  string|array $dataOrFilename data array or filename
     * @return object[]
     */
    public function load($dataOrFilename)
    {
        // ensure our data is loaded
        $data = !is_array($dataOrFilename) ? $this->parseFile($dataOrFilename) : $dataOrFilename;

        // create fixtures
        $newFixtures = $this->buildFixtures($data);

        // instantiate fixtures
        $this->instantiateFixtures($newFixtures);

        // populate objects
        return $this->populateObjects($newFixtures);
    }

    /**
     * Returns a reference to a fixture by name
     *
     * @param  string $name
     * @param  string $property optionally return only a given property of the reference
     * @return object
     */
    public function getReference($name, $property = null)
    {
        return $this->objects->find($name, $property);
    }

    /**
     * Returns all references created by the loader
     *
     * @return object[]
     */
    public function getReferences()
    {
        return $this->objects->toArray();
    }

    /**
     * @param array $providers
     */
    public function setProviders(array $providers)
    {
        $this->fakerProcessorMethod->setProviders(array_merge($this->getBuiltInProviders(), $providers));
    }

    /**
     * @param object|array $provider Provider or array of providers
     */
    public function addProvider($provider)
    {
        $this->fakerProcessorMethod->addProvider($provider);
    }

    /**
     * References are objects which the loader is aware of while loading fixtures.
     *
     * @param object[] $references Array of object where the key is the name of the reference
     */
    public function setReferences(array $references): void
    {
        $this->objects->clear();
        foreach ($references as $name => $object) {
            $this->objects->set($name, $object);
        }
    }

    /**
     * adds a processor for processing extensions
     **/
    public function addProcessor(ProcessorMethodInterface $processor)
    {
        $this->processor->addProcessor($processor);
    }

    /**
     * adds a parser for fixture parsing extensions
     **/
    public function addParser(ParserMethodInterface $parser)
    {
        $this->parser->addParser($parser);
    }

    /**
     * adds a builder for fixture building extensions
     **/
    public function addBuilder(BuilderMethodInterface $builder)
    {
        $this->builder->addBuilder($builder);
    }

    /**
     * Adds an instantiator for instantiation extensions.
     **/
    public function addInstantiator(InstantiatorMethodInterface $instantiator)
    {
        $this->instantiator->addInstantiator($instantiator);
    }

    /**
     * adds a populator for population extensions
     **/
    public function addPopulator(PopulatorMethodInterface $populator)
    {
        $this->populator->addPopulator($populator);
    }

    /**
     * parses a file at the given filename
     */
    protected function parseFile(string $filename): ?array
    {
        return $this->parser->parse($filename);
    }

    /**
     * builds a collection of fixtures
     *
     * @param  array     $rawData
     * @return Fixture[]
     */
    protected function buildFixtures(array $rawData): array
    {
        $fixtures = [];

        foreach ($rawData as $class => $specs) {
            $this->log('Loading '.$class);
            foreach ($specs as $name => $spec) {
                $fixtures[] = $this->builder->build($class, $name, null !== $spec ? $spec : []);
            }
        }

        return $fixtures ? call_user_func_array('array_merge', $fixtures) : [];
    }

    /**
     * creates an empty instance for each fixture, and adds it to our object collection
     *
     * @param Fixture[] $fixtures
     */
    protected function instantiateFixtures(array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            $this->objects->set(
                $fixture->getName(),
                $this->instantiator->instantiate($fixture)
            );
        }
    }

    /**
     * hydrates each instance described by fixtures and returns the final non-local list
     *
     * @param  Fixture[] $fixtures
     * @return object[]  List of object created
     */
    protected function populateObjects(array $fixtures)
    {
        $objects = [];

        foreach ($fixtures as $fixture) {
            $this->objects->set('self', $this->objects->get($fixture->getName()));
            $this->populator->populate($fixture);
            $this->objects->remove('self');

            // add the object in the object store unless it's local
            if (!$fixture->isLocal()) {
                $objects[$fixture->getName()] = $this->getReference($fixture->getName());
            }
        }

        return $objects;
    }

    /**
     * public interface to set the Persister interface
     *
     * @param PersisterInterface $manager
     */
    public function setPersister(PersisterInterface $manager)
    {
        $this->manager = $manager;
        $this->typeHintChecker->setPersister($manager);
    }

    /**
     * Set the logger callable to execute with the log() method.
     *
     * @param callable|LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return ParameterBag
     */
    public function getParameterBag()
    {
        return $this->parameterBag;
    }

    /**
    * Logs a message using the logger.
    *
    * @param string $message
    */
    public function log($message)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($message);
        } elseif ($logger = $this->logger) {
            $logger($message);
        }
    }

    /**
     * @return Faker
     */
    public function getFakerProcessorMethod()
    {
        return $this->fakerProcessorMethod;
    }

    /**
     * returns a list of all the default providers faker processing
     *
     * @return array
     */
    private function getBuiltInProviders()
    {
        return [new IdentityProvider()];
    }

    /**
     * returns a list of all the default processor methods
     *
     * @param  array  $providers - a list of all providers to build the processors with
     * @param  string $locale
     * @return array
     */
    private function getBuiltInProcessors(array $providers, $locale)
    {
        $this->fakerProcessorMethod = new Faker($providers, $locale);

        return [
            new Parameterized($this->parameterBag),
            new ArrayValue(),
            new Conditional(),
            $this->fakerProcessorMethod,
            new Reference(),
            new UnescapeAt(),
        ];
    }

    /**
     * returns a list of all the default parser methods
     *
     * @return array
     */
    private function getBuiltInParsers()
    {
        return [
            new Php($this),
            new Yaml($this),
        ];
    }

    /**
     * returns a list of all the default builder methods
     *
     * @return BuilderMethodInterface[]
     */
    private function getBuiltInBuilders(): array
    {
        return [
            new ReferenceRangeName($this->objects),
            new RangeName(),
            new ListName(),
            new SimpleName(),
        ];
    }

    /**
     * Returns a list of all the default instantiator methods.
     *
     * @return InstantiatorMethodInterface[]
     */
    private function getBuiltInInstantiators(Processor\Processor $processor, TypeHintChecker $typeHintChecker): array
    {
        return [
            new ReflectionWithoutConstructor(),
            new ReflectionWithConstructor($processor, $typeHintChecker),
            new EmptyConstructor(),
        ];
    }

    /**
     * returns a list of all the default populator methods
     *
     * @param  TypeHintChecker $typeHintChecker
     * @return array
     */
    private function getBuiltInPopulators(TypeHintChecker $typeHintChecker): array
    {
        return [
            new ArrayAdd($typeHintChecker),
            new Custom(),
            new ArrayDirect($typeHintChecker),
            new Direct($typeHintChecker),
            new Property(),
            new MagicCall(),
        ];
    }
}
