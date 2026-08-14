<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Table\ContactMessagesTable;
use Cake\Http\Response;

/**
 * Admin ContactMessages Controller
 *
 * Lets the administrator review and manage messages submitted through
 * the public contact form. All actions require authentication.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Cake\Controller\Component\PaginatorComponent $Paginator
 */
class ContactMessagesController extends AppController
{
    /**
     * The contact messages table.
     *
     * @var \App\Model\Table\ContactMessagesTable
     */
    protected ContactMessagesTable $ContactMessages;

    /**
     * Controller initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->ContactMessages = $this->fetchTable('ContactMessages');
        $this->viewBuilder()->addHelper('Paginator');
    }

    /**
     * Index method: paginated list of contact messages, newest first.
     *
     * The "n unread" pill on this page reads `$unreadCount`, which
     * AppController::beforeRender() already sets for the navigation badge.
     * Counting it again here ran the same COUNT twice in one render.
     *
     * @return void
     */
    public function index(): void
    {
        $query = $this->ContactMessages->find()
            ->orderBy(['ContactMessages.is_read' => 'ASC', 'ContactMessages.created' => 'DESC']);

        $contactMessages = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('contactMessages'));
    }

    /**
     * View method: show a single message and mark it as read.
     *
     * @param string|null $id ContactMessage id.
     * @return void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contactMessage = $this->ContactMessages->get($this->recordId($id));

        if (!$contactMessage->is_read) {
            $contactMessage->is_read = true;
            $this->ContactMessages->save($contactMessage);
        }

        $this->set(compact('contactMessage'));
    }

    /**
     * Delete method.
     *
     * @param string|null $id ContactMessage id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $contactMessage = $this->ContactMessages->get($this->recordId($id));
        if ($this->ContactMessages->delete($contactMessage)) {
            $this->Flash->success(__('The message has been deleted.'));
        } else {
            $this->Flash->error(__('The message could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
