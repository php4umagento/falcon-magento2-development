<?php
declare(strict_types=1);

namespace Deity\CatalogSearch\Model;

use Deity\CatalogApi\Api\Data\ProductSearchResultsInterface;
use Deity\CatalogApi\Api\Data\ProductSearchResultsInterfaceFactory;
use Deity\CatalogApi\Api\ProductConvertInterface;
use Deity\CatalogSearchApi\Api\ProductFilterProviderInterface;
use Deity\CatalogSearchApi\Api\QueryCollectionServiceInterface;
use Deity\CatalogSearchApi\Api\SearchInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Class CatalogSearchProductList
 *
 * @package Deity\CatalogSearch\Model
 */
class CatalogSearchProductList implements SearchInterface
{
    /**
     * @var QueryCollectionServiceInterface
     */
    protected $queryCollectionService;

    /**
     * @var Config
     */
    protected $catalogConfig;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Model\Layer\Search
     */
    private $searchLayer;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $productSearchResultFactory;

    /**
     * @var ProductConvertInterface
     */
    private $productConverter;

    /**
     * @var ProductFilterProviderInterface
     */
    private $filterProvider;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * CatalogSearchProductList constructor.
     * @param ProductSearchResultsInterfaceFactory $productSearchResultFactory
     * @param ProductConvertInterface $convert
     * @param Resolver $layerResolver
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFilterProviderInterface $productFilterProvider
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactory $collectionFactory
     * @param Config $catalogConfig
     * @param Visibility $productVisibility
     * @param QueryCollectionServiceInterface $queryCollectionService
     */
    public function __construct(
        ProductSearchResultsInterfaceFactory $productSearchResultFactory,
        ProductConvertInterface $convert,
        Resolver $layerResolver,
        ProductRepositoryInterface $productRepository,
        ProductFilterProviderInterface $productFilterProvider,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $collectionFactory,
        Config $catalogConfig,
        Visibility $productVisibility,
        QueryCollectionServiceInterface $queryCollectionService
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->filterProvider = $productFilterProvider;
        $this->productConverter = $convert;
        $this->productSearchResultFactory = $productSearchResultFactory;
        // this is required to create search layer rather than catalog
        $layerResolver->create('search');
        $this->searchLayer = $layerResolver->get();
        $this->layerResolver = $layerResolver;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->productVisibility = $productVisibility;
        $this->queryCollectionService = $queryCollectionService;
    }

    /**
     * @inheritdoc
     */
    public function search(SearchCriteriaInterface $searchCriteria, string $query): ProductSearchResultsInterface
    {
        $responseProducts = [];
        $layer = $this->layerResolver->get();
        $collection = $layer->getProductCollection();

        $collection
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->setVisibility($this->productVisibility->getVisibleInSearchIds());

        $this->collectionProcessor->process($searchCriteria, $collection);
        $this->queryCollectionService->apply($collection, $query);

        foreach ($collection->getItems() as $product) {
            $responseProducts[] = $this->productConverter->convert(
                $product
            );
        }

        $productSearchResult = $this->productSearchResultFactory->create();
        $productSearchResult->setFilters(
            $this->filterProvider->getFilterList(
                $this->layerResolver->get()
            )
        );
        $productSearchResult->setItems($responseProducts);
        $productSearchResult->setTotalCount($collection->getSize());
        return $productSearchResult;
    }
}