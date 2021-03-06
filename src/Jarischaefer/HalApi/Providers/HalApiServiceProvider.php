<?php namespace Jarischaefer\HalApi\Providers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\CacheFactoryImpl;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Caching\HalApiCacheImpl;
use Jarischaefer\HalApi\Middleware\HalApiCacheMiddleware;
use Jarischaefer\HalApi\Middleware\HalApiETagMiddleware;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\HalApiRepresentationImpl;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Representations\RepresentationFactoryImpl;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;
use Jarischaefer\HalApi\Transformers\TransformerFactory;
use Jarischaefer\HalApi\Transformers\TransformerFactoryImpl;

/**
 * Class HalApiServiceProvider
 * @package Jarischaefer\HalApi\Providers
 */
class HalApiServiceProvider extends ServiceProvider
{

	/**
	 * Base path in the vendor folder.
	 */
	const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
	/**
	 * List of files for Laravel's compiled.php.
	 */
	const COMPILES = [
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'CacheFactory.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'CacheFactoryImpl.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'HalApiCache.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'HalApiCacheImpl.php',

		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiController.php',
		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiControllerContract.php',
		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiResourceController.php',

		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'ResourceRoute.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'RouteHelper.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'RouteHelperConstants.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'SafeIndexArray.php',

		self::BASE_PATH . 'Middleware' . DIRECTORY_SEPARATOR . 'HalApiCacheMiddleware.php',
		self::BASE_PATH . 'Middleware' . DIRECTORY_SEPARATOR . 'HalApiETagMiddleware.php',

		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiRepresentation.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiRepresentationImpl.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiPaginatedRepresentation.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiPaginatedRepresentationImpl.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'RepresentationFactory.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'RepresentationFactoryImpl.php',

		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiLink.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiLinkImpl.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiUrlGenerator.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'LinkFactory.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'LinkFactoryImpl.php',

		self::BASE_PATH . 'Transformers' . DIRECTORY_SEPARATOR . 'HalApiTransformer.php',
		self::BASE_PATH . 'Transformers' . DIRECTORY_SEPARATOR . 'TransformerFactory.php',
		self::BASE_PATH . 'Transformers' . DIRECTORY_SEPARATOR . 'TransformerFactoryImpl.php',
	];

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * @inheritdoc
	 */
	public static function compiles()
	{
		return self::COMPILES;
	}

	/**
	 * @param Router $router
	 */
	public function boot(Router $router)
	{
		$router->middleware(HalApiETagMiddleware::NAME, HalApiETagMiddleware::class);
		$router->middleware(HalApiCacheMiddleware::NAME, HalApiCacheMiddleware::class);
		$this->app->singleton(RouteHelper::class, function() use ($router) {
			return new RouteHelper($router);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(HalApiRepresentation::class, HalApiRepresentationImpl::class);
		$this->app->bind(HalApiCache::class, HalApiCacheImpl::class);

		$this->app->singleton(Builder::class, function (Application $application) {
			/** @var DatabaseManager $databaseManager */
			$databaseManager = $application->make(DatabaseManager::class);

			return $databaseManager->connection()->getSchemaBuilder();
		});
		$this->app->singleton(CacheFactory::class, CacheFactoryImpl::class);
		$this->app->singleton(TransformerFactory::class, TransformerFactoryImpl::class);
		$this->app->singleton(RepresentationFactory::class, RepresentationFactoryImpl::class);
		$this->app->singleton(LinkFactory::class, function(Application $application) {
			return new LinkFactoryImpl($application->make(HalApiUrlGenerator::class));
		});
		$this->app->singleton(HalApiUrlGenerator::class, function(Application $application) {
			/** @var Router $router */
			$router = $application->make(Router::class);
			$request = $application->make(Request::class);
			return new HalApiUrlGenerator($router->getRoutes(), $request);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
