<?php

declare(strict_types=1);

use App\Application;
use App\Chain\PackingStrategyChain;
use App\Client\BinPacking3DClient;
use App\Factory\BinPackingShipmentPayloadFactory;
use App\Formatter\ErrorResponseFormatter;
use App\Handler\PackingResponseHandler;
use App\Provider\DoctrineBoxProvider;
use App\Provider\StaticBoxProvider;
use App\Service\PackingService;
use App\Strategy\BinPacking3DStrategy;
use App\Strategy\FallbackPackingStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

/** @var EntityManagerInterface $entityManager */
$entityManager = require __DIR__ . '/src/bootstrap.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$logger = new Logger('dev');
$logger->pushHandler(new StreamHandler('var/log/dev.log'));

$client = new TraceableHttpClient(HttpClient::create());

$request = Request::create(
    'pack',
    Request::METHOD_POST,
    server: ['CONTENT_TYPE' => 'application/json'],
    content: $argv[1],
);

$phpDocExtractor = new PhpDocExtractor();
$reflectionExtractor = new ReflectionExtractor();
$propertyInfo = new PropertyInfoExtractor(
    [$reflectionExtractor],
    [$phpDocExtractor, $reflectionExtractor],
    [$phpDocExtractor],
    [$reflectionExtractor]
);

$classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
$nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

$objectNormalizer = new ObjectNormalizer(
    $classMetadataFactory,
    $nameConverter,
    null,
    $propertyInfo
);

$serializer = new Serializer(
    [new ArrayDenormalizer(), $objectNormalizer],
    [new JsonEncoder()]
);

$binPacking3DClient = new BinPacking3DClient(
    $client,
    $serializer,
    $_ENV['API_3DBP_URL'],
    $_ENV['API_3DBP_USERNAME'],
    $_ENV['API_3DBP_KEY'],
);

$doctrineBoxProvider = new DoctrineBoxProvider($entityManager);
$staticBoxProvider = new StaticBoxProvider();

$binPackingShipmentPayloadFactory = new BinPackingShipmentPayloadFactory();
$packingResponseHandler = new PackingResponseHandler($logger);

$binPacking3DStrategy = new BinPacking3DStrategy(
    $binPacking3DClient,
    $doctrineBoxProvider, // swap possibility
    $binPackingShipmentPayloadFactory,
    $packingResponseHandler,
    $logger
);

$fallbackPackingStrategy = new FallbackPackingStrategy($doctrineBoxProvider);
$packingStrategyChain = new PackingStrategyChain([ // strategy configuration
    $binPacking3DStrategy,
    $fallbackPackingStrategy
]);

$cache = new FilesystemAdapter();
//$cache->clear();

$validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

$packingService = new PackingService($entityManager, $cache, $packingStrategyChain);
$errorResponseFormatter = new ErrorResponseFormatter();

$application = new Application($serializer, $validator, $packingService, $errorResponseFormatter, $logger);
$response = $application->run($request);

foreach ($client->getTracedRequests() as $i => $tracedRequest) {
    echo sprintf('%d. request : %s', $i + 1, json_encode($tracedRequest));
}

echo "<<< In:\n";
echo "Method: " . $request->getMethod() . "\n";
echo "URI: " . $request->getUri() . "\n";
echo "Headers:\n";
foreach ($request->headers->all() as $key => $value) {
    echo sprintf("  %s: %s\n", $key, implode(', ', $value));
}

echo "Request:\n" . $request->getContent() . "\n";

echo ">>> Out:\n";
echo sprintf("Status: %s\n", $response->getStatusCode());
echo "Headers:\n";
foreach ($response->headers->all() as $key => $value) {
    echo sprintf("  %s: %s\n", $key, implode(', ', $value));
}

echo "Response:\n" . json_encode(json_decode($response->getContent(), true), JSON_PRETTY_PRINT) . "\n";
