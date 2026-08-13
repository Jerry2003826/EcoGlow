<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Catalog\CatalogService;
use Cake\Http\Response;

/**
 * Storefront catalogue. Renders the existing Pages templates.
 */
class ShopController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['index', 'product']);
        $this->viewBuilder()->setTemplatePath('Pages');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $this->set('products', (new CatalogService())->shopProducts());

        return $this->render('shop');
    }

    /**
     * @param string|null $slug Product slug.
     * @return \Cake\Http\Response|null
     */
    public function product(?string $slug = null): ?Response
    {
        $this->set((new CatalogService())->productPage($slug));

        return $this->render('product');
    }
}
