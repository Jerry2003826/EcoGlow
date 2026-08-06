<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Expose the unread contact-message count to the navigation bar.
     *
     * Computed here (rather than in the layout template) so the view stays
     * free of ORM calls and the query only runs for authenticated users.
     * The Error controller is skipped so error pages never trigger a query.
     *
     * @param \Cake\Event\EventInterface $event The beforeRender event.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $unreadCount = 0;
        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && $this->request->getParam('controller') !== 'Error') {
            $unreadCount = $this->fetchTable('ContactMessages')
                ->find()
                ->where(['ContactMessages.is_read' => false])
                ->count();
        }

        $this->set('navUnreadCount', $unreadCount);
    }

    /**
     * Cast a record id captured from the URL to an integer.
     *
     * Passing a missing or non-numeric id straight to `Table::get()` raises an
     * uncaught exception and a 500 response, so reject those as 404s instead.
     *
     * @param string|null $id The id passed by the router.
     * @return int
     * @throws \Cake\Http\Exception\NotFoundException When the id is missing or not numeric.
     */
    protected function recordId(?string $id): int
    {
        if ($id === null || !ctype_digit($id)) {
            throw new NotFoundException();
        }

        return (int)$id;
    }
}
