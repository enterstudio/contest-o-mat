<?php

/***** Config *****/
if (! file_exists(APP_DIR.'/configs/global.php')) {
    exit('No global config file found. Please create one (app/configs/global.php)!');
}

$app->register(
    new Igorw\Silex\ConfigServiceProvider(
        APP_DIR.'/configs/global.php'
    )
);

// This local file should only work on localhost
if (
    file_exists(APP_DIR.'/configs/global-local.php')
    && in_array(
        @$_SERVER['REMOTE_ADDR'],
        array(
            '127.0.0.1',
            'fe80::1',
            '::1',
        )
    )
) {
    $app->register(
        new Igorw\Silex\ConfigServiceProvider(
                APP_DIR.'/configs/global-local.php'
        )
    );
}

/* Environment */
$app['environment'] = isset($app['environment'])
    ? $app['environment']
    : 'development'
;

/* Environment - Variable */
$environmentVariable = getenv('APPLICATION_ENVIRONMENT');
if (
    $environmentVariable &&
    in_array($environmentVariable, $app['environments'])
) {
    $app['environment'] = $environmentVariable;
}

/* Config - Environment */
if (file_exists(APP_DIR.'/configs/environments/'.$app['environment'].'.php')) {
    $app->register(
        new Igorw\Silex\ConfigServiceProvider(
            APP_DIR.'/configs/environments/'.$app['environment'].'.php'
        )
    );
}

/* Environment variables */
// In case you don't want to share your (production) password in the repository
if (getenv('APPLICATION_DATABASE_PASSWORD')) {
    $app['databaseOptions']['default']['password'] = getenv('APPLICATION_DATABASE_PASSWORD');
}

/*** Var / Storage directoy check ***/
// We NEED the storage directory. Else the app will not work
if (! file_exists(STORAGE_DIR)) {
    exit('The storage directory (var/) does not exists yet! Please run "bin/console application:storage:prepare" to prepare the storage directory.');
}

/***** Session *****/
$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.save_path' => STORAGE_DIR.'/sessions',
    'session.storage.options' => array(
        'cookie_path' => $app['baseUri'],
    ),
));

/* Flashbag */
$app['flashbag'] = $app->share(function () use ($app) {
    return $app['session']->getFlashBag();
});

/***** Http Cache *****/
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => STORAGE_DIR.'/cache/http/',
));

/***** Translation *****/
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en_US',
));

$app['translator']->addLoader(
    'yaml',
    new Symfony\Component\Translation\Loader\YamlFileLoader()
);

/*** Application Translator ***/
$app['application.translator'] = $app->share(function () use ($app) {
    return new \Application\Translator($app);
});

/*** Application Mailer ***/
$app['application.mailer'] = $app->share(function () use ($app) {
    return new \Application\Mailer($app);
});

/*** Paginator ***/
$app['paginator'] = $app->share(function () use ($app) {
    return new \Application\Paginator($app);
});

/***** Form *****/
$app->register(new Silex\Provider\FormServiceProvider());

/***** Twig / Templating Engine *****/
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => APP_DIR.'/templates',
        'twig.form.templates' => array(
            'twig/form.html.twig',
        ),
        'twig.options' => array(
            'cache' => STORAGE_DIR.'/cache/template',
            'debug' => $app['debug'],
        ),
    )
);

/*** Twig Extensions ***/
$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->addExtension(new Application\Twig\DateExtension($app));
    $twig->addExtension(new Application\Twig\FormExtension($app));
    $twig->addExtension(new Application\Twig\FileExtension($app));
    $twig->addExtension(new Application\Twig\UiExtension($app));
    $twig->addExtension(new Application\Twig\PaginatorExtension($app));
    $twig->addExtension(
        new Cocur\Slugify\Bridge\Twig\SlugifyExtension(
            Cocur\Slugify\Slugify::create()
        )
    );

    return $twig;
}));

/***** Translation *****/
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en_US',
));

$app['translator']->addLoader(
    'yaml',
    new Symfony\Component\Translation\Loader\YamlFileLoader()
);

/*** Application Translator ***/
$app['application.translator'] = $app->share(function () use ($app) {
    return new \Application\Translator($app);
});

/*** Application Mailer ***/
$app['application.mailer'] = $app->share(function () use ($app) {
    return new \Application\Mailer($app);
});

/***** Doctrine Database & Doctrine ORM *****/
if (
    isset($app['databaseOptions']) &&
    is_array($app['databaseOptions'])
) {
    $app->register(
        new Silex\Provider\DoctrineServiceProvider(),
        array(
            'dbs.options' => $app['databaseOptions'],
        )
    );

    $app->register(
        new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(),
        array(
            'orm.em.options' => array(
                'mappings' => array(
                    array(
                        'type' => 'annotation',
                        'namespace' => 'Application\Entity',
                        'path' => SRC_DIR.'/Application/Entity',
                        'use_simple_annotation_reader' => false,
                    ),
                ),
            ),
            'orm.custom.functions.string' => array(
                'cast' => 'Oro\ORM\Query\AST\Functions\Cast',
                'group_concat' => 'Oro\ORM\Query\AST\Functions\String\GroupConcat',
            ),
            'orm.custom.functions.datetime' => array(
                'date' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'time' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'timestamp' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'convert_tz' => 'Oro\ORM\Query\AST\Functions\DateTime\ConvertTz',
            ),
            'orm.custom.functions.numeric' => array(
                'timestampdiff' => 'Oro\ORM\Query\AST\Functions\Numeric\TimestampDiff',
                'dayofyear' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'dayofweek' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'week' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'day' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'hour' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'minute' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'month' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'quarter' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'second' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'year' => 'Oro\ORM\Query\AST\Functions\SimpleFunction',
                'sign' => 'Oro\ORM\Query\AST\Functions\Numeric\Sign',
                'pow' => 'Oro\ORM\Query\AST\Functions\Numeric\Pow',
            ),
        )
    );

    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(
        array(
            require VENDOR_DIR.'/autoload.php',
            'loadClass',
        )
    );

    $entityManagerConfig = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        array(APP_DIR.'/src/Application/Entity'),
        $app['debug']
    );

    $entityManager = Doctrine\ORM\EntityManager::create(
        $app['dbs.options']['default'],
        $entityManagerConfig
    );

    Doctrine\Common\Persistence\PersistentObject::setObjectManager(
        $entityManager
    );

    $app['orm.proxies_dir'] = STORAGE_DIR.'/cache/proxy';

    $app['orm.manager_registry'] = $app->share(function ($app) {
        return new \Application\Doctrine\ORM\DoctrineManagerRegistry(
            'manager_registry',
            array('default' => $app['orm.em']->getConnection()),
            array('default' => $app['orm.em'])
        );
    });

    $app['form.extensions'] = $app->share(
        $app->extend(
            'form.extensions',
            function ($extensions) use ($app) {
                $extensions[] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension(
                    $app['orm.manager_registry']
                );

                return $extensions;
            }
        )
    );
}

/***** Validator *****/
$app->register(new \Silex\Provider\ValidatorServiceProvider());

$app['validator.mapping.mapping.file_path'] = APP_DIR.'/configs/validation.yml';

$app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
    return new Symfony\Component\Validator\Mapping\ClassMetadataFactory(
        new Symfony\Component\Validator\Mapping\Loader\YamlFileLoader(
            APP_DIR.'/configs/validation.yml'
        )
    );
});

$app['validator.unique_entity'] = $app->share(function ($app) {
    return new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator(
        $app['orm.manager_registry']
    );
});

$app['security.validator.user_password'] = $app->share(function ($app) {
    return new Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator(
        $app['security'],
        $app['security.encoder_factory']
    );
});

$app['validator.validator_service_ids'] = array(
    'doctrine.orm.validator.unique' => 'validator.unique_entity',
    'security.validator.user_password' => 'security.validator.user_password',
);

/***** Url Generator *****/
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

/***** Http Cache *****/
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => STORAGE_DIR.'/cache/http/',
));

/***** User Provider *****/
$app['user.provider'] = $app->share(function () use ($app) {
    return new \Application\Provider\UserProvider($app);
});

/*** UAParser ***/
$app['ua.parser'] = $app->share(function () use ($app) {
    return \UAParser\Parser::create();
});

/*** Mobile Detect ***/
$app->register(
    new Binfo\Silex\MobileDetectServiceProvider()
);

/*** UAParser ***/
$app['ip.parser'] = $app->share(function () use ($app) {
    return new \Application\IPParser();
});

/***** Security *****/
$securityFirewalls = array();

/*** Members Area ***/
$securityFirewalls['members-area'] = array(
    'pattern' => '^/',
    'anonymous' => true,
    'form' => array(
        'login_path' => '/members-area/login',
        'check_path' => '/members-area/login/check',
        'failure_path' => '/members-area/login',
        'default_target_path' => '/members-area',
        'use_referer' => true,
        'username_parameter' => 'username',
        'password_parameter' => 'password',
        'csrf_protection' => true,
        'csrf_parameter' => 'csrf_token',
        'with_csrf' => true,
        'use_referer' => true,
    ),
    'logout' => array(
        'logout_path' => '/members-area/logout',
        'target' => '/members-area',
        'invalidate_session' => true,
        'csrf_parameter' => 'csrf_token',
    ),
    'remember_me' => $app['rememberMeOptions'],
    'switch_user' => array(
        'parameter' => 'switch_user',
        'role' => 'ROLE_ALLOWED_TO_SWITCH',
    ),
    'users' => $app['user.provider'],
);

$app->register(
    new Silex\Provider\SecurityServiceProvider(),
    array(
        'security.firewalls' => $securityFirewalls,
    )
);

$app['security.access_rules'] = array(
    array('^/members-area/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array('^/members-area/register', 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array('^/members-area/reset-password', 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array('^/members-area', 'ROLE_USER'),
    array('^/members-area/oauth', 'ROLE_USER'),
);

$app['security.role_hierarchy'] = array(
    'ROLE_SUPER_ADMIN' => array('ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'),
    'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
);

/* Voters */
$app['security.voters'] = $app->extend(
    'security.voters',
    function ($voters) use ($app) {
        // Soon!

        return $voters;
    }
);

/***** Remember Me *****/
$app->register(new Silex\Provider\RememberMeServiceProvider());

/***** Swiftmailer / Mailer *****/
$app->register(new Silex\Provider\SwiftmailerServiceProvider());

$app['swiftmailer.options'] = $app['swiftmailerOptions'];

$app['mailer.css_to_inline_styles_converter'] = $app->protect(function ($twigTemplatePathOrContent, $twigTemplateData = array(), $isTwigTemplate = true) use ($app) {
    $emogrifier = new \Pelago\Emogrifier();
    $emogrifier->setHtml(
        $isTwigTemplate
        ? $app['twig']->render($twigTemplatePathOrContent, $twigTemplateData)
        : $app['twig']->render(
            'emails/blank.html.twig',
            array_merge(
                $twigTemplateData,
                array(
                    'content' => $twigTemplatePathOrContent,
                )
            )
        )
    );

    return $emogrifier->emogrify();
});

/*** Facebook SDK ***/
$app['facebookSdk'] = false;
if (
    $app['facebookSdkOptions']['id'] &&
    $app['facebookSdkOptions']['secret']
) {
    $app['facebookSdk'] = $app->share(function () use ($app) {
        return new \Facebook\Facebook(array(
            'app_id' => $app['facebookSdkOptions']['id'],
            'app_secret' => $app['facebookSdkOptions']['secret'],
            'default_graph_version' => $app['facebookSdkOptions']['version'],
        ));
    });
}

/*** Listeners ***/
$app['dispatcher']->addListener(
    \Symfony\Component\Security\Core\AuthenticationEvents::AUTHENTICATION_SUCCESS,
    function ($event) use ($app) {
        $user = $event->getAuthenticationToken()->getUser();

        $user
            ->setTimeLastActive(
                new \DateTime()
            )
        ;

        $app['orm.em']->merge($user);
        $app['orm.em']->flush();
    }
);
