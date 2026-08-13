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

use App\Model\Entity\User;
use App\Service\Authorization\PermissionService;
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
     * Expose the unread contact-message count to every authenticated render.
     *
     * This is the single source of that number. It is computed here rather than
     * in the layout so the view stays free of ORM calls, and it runs after the
     * action so the count reflects anything the action just changed — such as
     * a message being marked read on its way to being displayed.
     *
     * Two things read it: the navigation badge in the default layout, and the
     * "n unread" pill on the admin message list. The admin controller used to
     * run the identical COUNT again for its own copy, which meant two of the
     * same query in one render.
     *
     * The Error controller is skipped so error pages never trigger a query.
     *
     * @param \Cake\Event\EventInterface $event The beforeRender event.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $unreadCount = 0;
        $isStaff = false;
        $isCustomer = false;
        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && $this->request->getParam('controller') !== 'Error') {
            $userId = (int)$identity->getIdentifier();
            $user = $this->fetchTable('Users')->get($userId);
            $isStaff = $this->isStaffUser($user);
            $isCustomer = $this->isCustomerUser($user);
            if ($isStaff) {
                $unreadCount = $this->fetchTable('ContactMessages')
                    ->find()
                    ->where(['ContactMessages.is_read' => false])
                    ->count();
            }
        }

        $this->set(compact('unreadCount', 'isStaff', 'isCustomer'));
    }

    /**
     * Staff are anyone with a non-customer role, or any RBAC grant.
     *
     * @param \App\Model\Entity\User $user User.
     * @return bool
     */
    protected function isStaffUser(User $user): bool
    {
        $role = (string)($user->get('role') ?: '');
        if ($role !== '' && $role !== 'customer') {
            return true;
        }

        return (new PermissionService())->hasAny((int)$user->id);
    }

    /**
     * Customer portal identity: role is customer and they hold no staff grants.
     *
     * @param \App\Model\Entity\User $user User.
     * @return bool
     */
    protected function isCustomerUser(User $user): bool
    {
        return (string)($user->get('role') ?: '') === 'customer'
            && !(new PermissionService())->hasAny((int)$user->id);
    }

    /**
     * Where to send someone after a successful sign-in.
     *
     * @param \App\Model\Entity\User $user User.
     * @return string
     */
    protected function afterLoginPath(User $user): string
    {
        return $this->isCustomerUser($user) ? '/account' : '/admin';
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
