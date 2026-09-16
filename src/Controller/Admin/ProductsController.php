<?php
// src/Controller/Admin/ProductsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use Psr\Http\Message\UploadedFileInterface;
use Throwable;

class ProductsController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        return $this->redirect('/admin/inventory');
    }

    public function add()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $productsTable = $this->fetchTable('Products');
        $product = $productsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $imageUpload = $data['Image'] ?? null;
            unset($data['Image']);

            if ($this->hasImageUpload($imageUpload)) {
                $imagePath = $this->storeProductImage($imageUpload);
                if ($imagePath === null) {
                    $this->setProductFormLists();
                    $this->set(compact('product'));

                    return;
                }

                $data['Image'] = $imagePath;
            }

            $product = $productsTable->patchEntity($product, $data);

            if ($productsTable->save($product)) {
                $inventoryTable = $this->fetchTable('Inventory');
                $existing = $inventoryTable
                    ->find()
                    ->where(['ProductId' => (int)$product->ProductId])
                    ->first();

                if (!$existing) {
                    $inventoryTable->save($inventoryTable->newEntity([
                        'ProductId' => (int)$product->ProductId,
                        'Quantity' => 0,
                        'LowStockThreshold' => 5,
                    ]));
                }

                $this->Flash->success('Product saved.');

                return $this->redirect('/admin/inventory');
            }

            $this->Flash->error('The product could not be saved.');
        }

        $this->setProductFormLists();
        $this->set(compact('product'));
    }

    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $productsTable = $this->fetchTable('Products');
        $product = $productsTable->get((int)$id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $imageUpload = $data['Image'] ?? null;
            unset($data['Image']);

            if ($this->hasImageUpload($imageUpload)) {
                $imagePath = $this->storeProductImage($imageUpload);
                if ($imagePath === null) {
                    $this->setProductFormLists();
                    $this->set(compact('product'));

                    return;
                }

                $data['Image'] = $imagePath;
            }

            $product = $productsTable->patchEntity($product, $data);

            if ($productsTable->save($product)) {
                $this->Flash->success('Product updated.');

                return $this->redirect('/admin/inventory');
            }

            $this->Flash->error('The product could not be updated.');
        }

        $this->setProductFormLists();
        $this->set(compact('product'));
    }

    public function delete(int|string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $orderItem = $this->fetchTable('OrderItems')
            ->find()
            ->where(['ProductId' => (int)$id])
            ->first();

        if ($orderItem) {
            $this->Flash->error('Product cannot be deleted while it appears on an order.');

            return $this->redirect('/admin/inventory');
        }

        $product = $this->fetchTable('Products')->get((int)$id);
        $imagePath = $product->Image;
        $inventory = $this->fetchTable('Inventory')
            ->find()
            ->where(['ProductId' => (int)$id])
            ->first();

        if ($inventory) {
            $this->fetchTable('Inventory')->delete($inventory);
        }

        if ($this->fetchTable('Products')->delete($product)) {
            $imageIsShared = $imagePath !== null && $this->fetchTable('Products')
                ->find()
                ->where(['Image' => $imagePath])
                ->count() > 0;
            $imageFilename = is_string($imagePath) ? basename($imagePath) : '';
            $isProductImage = is_string($imagePath)
                && str_starts_with($imagePath, 'product/')
                && $imageFilename !== ''
                && $imageFilename === substr($imagePath, strlen('product/'));

            if (!$imageIsShared && $isProductImage) {
                $imageFile = WWW_ROOT . 'img' . DS . 'product' . DS . $imageFilename;
                if (is_file($imageFile)) {
                    unlink($imageFile);
                }
            }

            $this->Flash->success('Product deleted.');
        } else {
            $this->Flash->error('The product could not be deleted.');
        }

        return $this->redirect('/admin/inventory');
    }

    private function setProductFormLists(): void
    {
        $categories = $this->fetchTable('ProductCategories')
            ->find('list', [
                'keyField' => 'CategoryId',
                'valueField' => 'CategoryName',
            ])
            ->orderBy(['CategoryName' => 'ASC'])
            ->toArray();

        $suppliers = $this->fetchTable('Suppliers')
            ->find('list', [
                'keyField' => 'SupplierId',
                'valueField' => 'SupplierName',
            ])
            ->orderBy(['SupplierName' => 'ASC'])
            ->toArray();

        $this->set(compact('categories', 'suppliers'));
    }

    /**
     * Check whether the request contains an attempted image upload.
     */
    private function hasImageUpload(mixed $imageUpload): bool
    {
        if ($imageUpload instanceof UploadedFileInterface) {
            return $imageUpload->getError() !== UPLOAD_ERR_NO_FILE;
        }

        return $imageUpload !== null && $imageUpload !== '';
    }

    /**
     * Validate and store a product image, returning its database path.
     */
    private function storeProductImage(mixed $imageUpload): ?string
    {
        if (!$imageUpload instanceof UploadedFileInterface) {
            $this->Flash->error('Please select a valid image file.');

            return null;
        }

        if ($imageUpload->getError() !== UPLOAD_ERR_OK) {
            $this->Flash->error('The image upload failed. Please try again.');

            return null;
        }

        $originalFilename = $imageUpload->getClientFilename() ?? '';
        $filename = basename(str_replace('\\', '/', $originalFilename));
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        $invalidFilename = $filename === '' || $filename === '.' || $filename === '..';
        if ($invalidFilename || !in_array($extension, $allowedExtensions, true)) {
            $this->Flash->error('Please upload a JPG, PNG, or WEBP image.');

            return null;
        }

        $temporaryPath = $imageUpload->getStream()->getMetadata('uri');
        $imageInfo = is_string($temporaryPath) ? getimagesize($temporaryPath) : false;
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

        if ($imageInfo === false || !in_array($imageInfo[2], $allowedImageTypes, true)) {
            $this->Flash->error('The uploaded file is not a valid JPG, PNG, or WEBP image.');

            return null;
        }

        $productDirectory = WWW_ROOT . 'img' . DS . 'product' . DS;
        if (!is_dir($productDirectory) && !mkdir($productDirectory, 0755, true) && !is_dir($productDirectory)) {
            $this->Flash->error('The product image folder is not available.');

            return null;
        }

        try {
            $imageUpload->moveTo($productDirectory . $filename);
        } catch (Throwable $exception) {
            $this->Flash->error('The product image could not be saved.');

            return null;
        }

        return 'product/' . $filename;
    }
}
