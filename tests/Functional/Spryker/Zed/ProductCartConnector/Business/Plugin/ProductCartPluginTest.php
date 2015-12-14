<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Functional\Spryker\Zed\ProductCartConnector\Business\Plugin;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Shared\Kernel\Store;
use Spryker\Zed\ProductCartConnector\Business\ProductCartConnectorFacade;
use Spryker\Zed\Locale\Business\LocaleFacade;
use Orm\Zed\Tax\Persistence\SpyTaxRate;
use Orm\Zed\Tax\Persistence\SpyTaxSet;
use Orm\Zed\Product\Persistence\SpyProductAbstract;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\Product\Persistence\SpyProductLocalizedAttributes;

/**
 * @group Spryker
 * @group Zed
 * @group ProductCartConnector
 * @group Business
 * @group ProductCartPlugin
 */
class ProductCartPluginTest extends Test
{

    const SKU_PRODUCT_ABSTRACT = 'Product abstract sku';
    const SKU_PRODUCT_CONCRETE = 'Product concrete sku';
    const TAX_SET_NAME = 'Sales Tax';
    const TAX_RATE_NAME = 'VAT';
    const TAX_RATE_PERCENTAGE = 10;
    const PRODUCT_CONCRETE_NAME = 'Product concrete name';

    /**
     * @var \Spryker\Zed\ProductCartConnector\Business\ProductCartConnectorFacade
     */
    private $productCartConnectorFacade;

    /**
     * @var \Spryker\Zed\Locale\Business\LocaleFacade
     */
    private $localeFacade;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->localeFacade = new LocaleFacade();
        $this->productCartConnectorFacade = new ProductCartConnectorFacade();
    }

    /**
     * @return void
     */
    public function testPluginExpandsCartItemWithExpectedProductData()
    {
        $localeName = Store::getInstance()->getCurrentLocale();
        $localeTransfer = $this->localeFacade->getLocale($localeName);

        $taxRateEntity = new SpyTaxRate();
        $taxRateEntity->setRate(self::TAX_RATE_PERCENTAGE)
            ->setName(self::TAX_RATE_NAME);

        $taxSetEntity = new SpyTaxSet();
        $taxSetEntity->addSpyTaxRate($taxRateEntity)
            ->setName(self::TAX_SET_NAME);

        $productAbstractEntity = new SpyProductAbstract();
        $productAbstractEntity->setSpyTaxSet($taxSetEntity)
            ->setAttributes('')
            ->setSku(self::SKU_PRODUCT_ABSTRACT);

        $localizedAttributesEntity = new SpyProductLocalizedAttributes();
        $localizedAttributesEntity->setName(self::PRODUCT_CONCRETE_NAME)
            ->setAttributes('')
            ->setFkLocale($localeTransfer->getIdLocale());

        $productConcreteEntity = new SpyProduct();
        $productConcreteEntity->setSpyProductAbstract($productAbstractEntity)
            ->setAttributes('')
            ->addSpyProductLocalizedAttributes($localizedAttributesEntity)
            ->setSku(self::SKU_PRODUCT_CONCRETE)
            ->save();

        $changeTransfer = new CartChangeTransfer();
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku(self::SKU_PRODUCT_CONCRETE);
        $changeTransfer->addItem($itemTransfer);

        $this->productCartConnectorFacade->expandItems($changeTransfer);

        $expandedItemTransfer = $changeTransfer->getItems()[0];

        $this->assertEquals(self::SKU_PRODUCT_ABSTRACT, $expandedItemTransfer->getAbstractSku());
        $this->assertEquals(self::SKU_PRODUCT_CONCRETE, $expandedItemTransfer->getSku());
        $this->assertEquals($productAbstractEntity->getIdProductAbstract(), $expandedItemTransfer->getIdProductAbstract());
        $this->assertEquals($productConcreteEntity->getIdProduct(), $expandedItemTransfer->getId());
        $expandedTSetTransfer = $expandedItemTransfer->getTaxSet();
        $this->assertNotNull($expandedTSetTransfer);
        $this->assertEquals(self::TAX_SET_NAME, $expandedTSetTransfer->getName());
    }

}
