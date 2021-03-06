<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Cms\Controller\Adminhtml\Wysiwyg;

use Magento\Backend\App\Action;
use Magento\Cms\Model\Template\Filter;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\Framework\Image\AdapterFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;

/**
 * Process template text for wysiwyg editor.
 *
 * Class Directive
 */
class Directive extends Action implements HttpGetActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Cms::media_gallery';

    /**
     * @var DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DecoderInterface $urlDecoder
     * @param RawFactory $resultRawFactory
     * @param AdapterFactory|null $adapterFactory
     * @param LoggerInterface|null $logger
     * @param Config|null $config
     * @param Filter|null $filter
     */
    public function __construct(
        Context $context,
        DecoderInterface $urlDecoder,
        RawFactory $resultRawFactory,
        AdapterFactory $adapterFactory = null,
        LoggerInterface $logger = null,
        Config $config = null,
        Filter $filter = null
    ) {
        parent::__construct($context);
        $this->urlDecoder = $urlDecoder;
        $this->resultRawFactory = $resultRawFactory;
        $this->adapterFactory = $adapterFactory ?: ObjectManager::getInstance()->get(AdapterFactory::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->config = $config ?: ObjectManager::getInstance()->get(Config::class);
        $this->filter = $filter ?: ObjectManager::getInstance()->get(Filter::class);
    }

    /**
     * Template directives callback
     *
     * @return Raw
     */
    public function execute()
    {
        $directive = $this->getRequest()->getParam('___directive');
        $directive = $this->urlDecoder->decode($directive);
        try {
            /** @var Filter $filter */
            $imagePath = $this->filter->filter($directive);
            /** @var AdapterInterface $image */
            $image = $this->adapterFactory->create();
            /** @var Raw $resultRaw */
            $resultRaw = $this->resultRawFactory->create();
            $image->open($imagePath);
            $resultRaw->setHeader('Content-Type', $image->getMimeType());
            $resultRaw->setContents($image->getImage());
        } catch (\Exception $e) {
            /** @var Config $config */
            $imagePath = $this->config->getSkinImagePlaceholderPath();
            try {
                $image->open($imagePath);
                $resultRaw->setHeader('Content-Type', $image->getMimeType());
                $resultRaw->setContents($image->getImage());
                $this->logger->warning($e);
            } catch (\Exception $e) {
                $this->logger->warning($e);
            }
        }
        return $resultRaw;
    }
}
