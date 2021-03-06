<?php

namespace PrestaShop\Demonstration\Test\Entity;

/*
 * 2007-2015 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\Demonstration\Entity\EntityFactory;
use Category;
use Context;
use Db;
use Product;

class EntityFactoryTest extends \PHPUnit_Framework_TestCase
{
    const NOTICE = '[Factory]';

    public function testCreateFromValuesNonManaged()
    {
        $this->assertNull(EntityFactory::createFromValues('unknown', []));
    }

    /**
     * @todo move this behavior to an helper function deleteFromDatabase($id, $tableName)
     */
    public function testCreateFromValuesWithProduct()
    {
        $fixturesImgPath = __DIR__.'/../fixtures/assets/';
        $returnProperties = EntityFactory::createFromValues('products', $this->fakeProductData(), $fixturesImgPath);

        $this->assertInternalType('array', $returnProperties);
        $this->assertTrue(Product::existsInDatabase($returnProperties['id'], $returnProperties['table_name']));

        $product = new \Product($returnProperties['id']);
        $product->deleteImages();
        $product->delete();
    }

    public function testCreateFromValuesWithCategory()
    {
        $fixturesImgPath = __DIR__.'/../fixtures/assets/';
        $returnProperties = EntityFactory::createFromValues('categories', $this->fakeCategoryData(), $fixturesImgPath);

        $this->assertInternalType('array', $returnProperties);
        $this->assertTrue(Category::existsInDatabase($returnProperties['id'], $returnProperties['table_name']));

        $category = new Category($returnProperties['id']);
        $category->deleteImage();
        $category->delete();
    }

    public function testCreateFromValuesWithCategories()
    {
        /* create Parent category */
        $fixturesImgPath = __DIR__.'/../fixtures/assets/';
        $returnProperties = EntityFactory::createFromValues('categories', $this->fakeCategoryData(), $fixturesImgPath);

        $this->assertInternalType('array', $returnProperties);
        $this->assertTrue(Category::existsInDatabase($returnProperties['id'], $returnProperties['table_name']));

        /* obviously, populate the demonstration table */
        Db::getInstance()->insert('demonstration', $returnProperties);

        $parentCategory = new Category($returnProperties['id']);


        /* create children category */
        $returnProperties2 = EntityFactory::createFromValues('categories', $this->fakeCategoryChildData(), $fixturesImgPath);
        $this->assertInternalType('array', $returnProperties2);
        $this->assertTrue(Category::existsInDatabase($returnProperties2['id'], $returnProperties2['table_name']));

        $childCategory = new Category($returnProperties['id']);
        $this->assertInstanceOf('Category', $parentCategory, sprintf(self::NOTICE.' Category creation fails: expected Category, received %', gettype($childCategory)));

        $nbParents = count($childCategory->getAllParents());

        $this->assertSame(2, $nbParents, sprintf(self::NOTICE.' Category creation fails: expected 2 parents (root & fixture one), received %', $nbParents));
        $parentCategory->delete();

        /* and remove entry from demonstration table */
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'demonstration` WHERE id ='. $parentCategory->id);
    }

    private function fakeProductData()
    {
        return [
            'name' => 'new product',
            'images' => [$this->fakeImageData(1), $this->fakeImageData(2)],
        ];
    }

    private function fakeCategoryData()
    {
        return [
            'ps_id' => '1',
            'name' => 'Category 1',
            'position' => 1,
            'description' => 'Category description 1',
            'image' => [
                'src' => 'category_1.jpg',
                'alt' => 'category alt 1',
                'cssClass' => 'cat cat-thumbnail',
            ],
        ];
    }

    private function fakeCategoryChildData()
    {
        return [
            'ps_id' => '2',
            'ps_parent' => '1',
            'name' => 'Category 1_1',
            'position' => 1,
            'description' => 'Category description 1_1',
            'image' => [
                'src' => 'category_1_1.jpg',
                'alt' => 'category alt 1_1',
                'cssClass' => 'cat cat-thumbnail',
            ],
        ];
    }

    private function fakeImageData($id)
    {
        return [
            'src' => "product_mini_$id.jpg",
            'alt' => "alt for id $id",
            'cssClass' => 'img img-thumbnail',
        ];
    }
}
