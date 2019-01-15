<?php
declare(strict_types=1);

namespace Deity\UrlRewrite\Model\UrlRewrite\CanonicalUrlProvider;

use Deity\UrlRewriteApi\Api\CanonicalUrlProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class ProductUrlProvider implements CanonicalUrlProviderInterface
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param $urlModel UrlRewrite
     * @return string
     */
    public function getCanonicalUrl(UrlRewrite $urlModel): string
    {
        try {
            $product = $this->productRepository->getById($urlModel->getEntityId());
        } catch (NoSuchEntityException $e) {
            return '';
        }

        return $product->getUrlModel()->getUrl($product, ['_ignore_category' => true]);
    }
}