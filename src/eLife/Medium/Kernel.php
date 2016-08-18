<?php

namespace eLife\Medium;

use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Medium\Model\Image;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Response\ExceptionResponse;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Json\JsonDecoder;
use Throwable;
use DateTimeImmutable;
use LogicException;

class Kernel
{
    const ROOT = __DIR__ . '/../../..';
    const CONFIG = self::ROOT . '/config.yml';

    public static function create() : Application
    {
        // Create application.
        $app = new Application();
        // Load config
        $app['config'] = self::loadConfig();
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT . '/vendor/jms/serializer/src'
        );
        // DI.
        self::dependencies($app);
        // Routes
        self::routes($app);
        // Validate.
        $app->after(function (Request $request, Response $response) use ($app) {
            // Validation.
            if ($app['config']['validate']) {
                self::validate($app, $request, $response);
            }
        }, 2);

        // Error handling.
        $app->error(function (Throwable $e) use ($app) {
            if ($app['debug']) {
                return null;
            }
            return self::handleException($e, $app);
        });
        return $app;
    }

    public static function loadConfig()
    {
        if (!file_exists(self::CONFIG)) {
            throw new LogicException('Configuration file not found');
        }
        return Yaml::parse(file_get_contents(self::CONFIG));
    }

    public static function dependencies(Application $app)
    {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT . '/cache')->build();
        };
        // Puli.
        $app['puli.factory'] = function () {
            $factoryClass = PULI_FACTORY_CLASS;
            return new $factoryClass();
        };
        // Puli repo.
        $app['puli.repository'] = function (Application $app) {
            return $app['puli.factory']->createRepository();
        };
        // PSR-7 Bridge
        $app['psr7.bridge'] = function (Application $app) {
            return new DiactorosFactory();
        };

        $app['puli.validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PuliSchemaFinder($app['puli.repository']),
                new JsonDecoder()
            );
        };
    }

    public static function validate(Application $app, Request $request, Response $response) {
        $app['puli.validator']->validate(
            $app['psr7.bridge']->createResponse($response)
        );
    }

    public static function routes(Application $app)
    {
        // Routes.
        $app->get('/', function () use ($app) {

            $article = new MediumArticle();
            $article->setTitle('Hardened hearts reveal organ evolution');
            $article->setUri('https://medium.com/life-on-earth/hardened-hearts-reveal-organ-evolution-8eb882a8bf18');
            $article->setImpactStatement('Fossilized hearts have been found in specimens of an extinct fish in Brazil.');
            $article->setGuid('8eb882a8bf18');
            $article->setPublished(new \DateTime());
            $article->setImageDomain('cdn-images-1.medium.com');
            $article->setImagePath('1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg');
            $article->setImageAlt('alt text');

            $article2 = new MediumArticle();
            $article2->setTitle('How do bacteria know when to attack?');
            $article2->setUri('https://medium.com/lifes-building-blocks/how-do-bacteria-know-when-to-attack-d8fe7a32514f');
            $article2->setGuid('d8fe7a32514f');
            $article2->setPublished(new \DateTime());
            $article2->setImageDomain('cdn-images-1.medium.com');
            $article2->setImagePath('1*2YPqnBTvdQSPhSmuhF3JOg.png');
            $article2->setImageAlt('alt text');

            $data = MediumArticleListResponse::mapFromEntities([ $article, $article2 ]);

            // Make the JSON.
            $json = $app['serializer']->serialize($data, 'json');

            // Get repo
            // Make query based on params (none)
            // return response object
            // serialize
            return new Response($json, 200, $data->getHeaders());
        });
    }

    public static function handleException(Throwable $e, Application $app)
    {
        return new Response(
            $app['serializer']->serialize(
                new ExceptionResponse($e->getMessage()),
                'json'
            ),
            403,
            ['Content-Type' => 'application/json']
        );
    }

}
