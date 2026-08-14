<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\ContactMessage;
use App\Model\Table\ContactMessagesTable;
use App\Service\Inventory\InventoryLedger;
use App\Service\Messages\MessageService;
use App\Service\OutboundQueue;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Staff inbox: status, assignment and queued replies.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class ContactMessagesController extends AdminController
{
    /**
     * @var \App\Model\Table\ContactMessagesTable
     */
    protected ContactMessagesTable $ContactMessages;

    /**
     * @var \App\Service\Messages\MessageService
     */
    private MessageService $messages;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->ContactMessages = $this->fetchTable('ContactMessages');
        $this->messages = new MessageService(new OutboundQueue(), new InventoryLedger());
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $status = (string)$this->request->getQuery('status', '');
        $query = $this->ContactMessages->find()
            ->contain(['AssignedUsers'])
            ->orderBy(['ContactMessages.is_read' => 'ASC', 'ContactMessages.created' => 'DESC']);
        if ($status !== '' && isset(ContactMessage::statusLabels()[$status])) {
            $query->where(['ContactMessages.status' => $status]);
        }

        $contactMessages = $this->paginate($query, ['limit' => 20]);
        $this->set(compact('contactMessages', 'status'));
    }

    /**
     * Read-only detail. Marking read is a separate POST.
     *
     * @param string|null $id Message id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $contactMessage = $this->ContactMessages->get($this->recordId($id), contain: [
            'AssignedUsers',
            'ContactMessageEvents' => ['Users'],
        ]);
        $staff = $this->staffOptions();
        $nextStatuses = ContactMessage::nextStatuses(
            (string)($contactMessage->status ?: ContactMessage::STATUS_NEW),
        );
        $this->set(compact('contactMessage', 'staff', 'nextStatuses'));
    }

    /**
     * @param string|null $id Message id.
     * @return \Cake\Http\Response|null
     */
    public function markRead(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $contactMessage = $this->ContactMessages->get($this->recordId($id));
        if (!$contactMessage->is_read) {
            $contactMessage->set('is_read', true);
            $this->ContactMessages->save($contactMessage);
        }
        $this->Flash->success(__('Message marked as read.'));

        return $this->redirect(['action' => 'view', $contactMessage->id]);
    }

    /**
     * @param string|null $id Message id.
     * @return \Cake\Http\Response|null
     */
    public function reply(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $contactMessage = $this->ContactMessages->get($this->recordId($id));
        try {
            $this->messages->reply(
                $contactMessage,
                (string)$this->request->getData('body'),
                $this->actorId(),
            );
            $this->Flash->success(__('Reply queued. It will send when the mail worker runs.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $contactMessage->id]);
    }

    /**
     * @param string|null $id Message id.
     * @return \Cake\Http\Response|null
     */
    public function updateStatus(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $contactMessage = $this->ContactMessages->get($this->recordId($id));
        try {
            $this->messages->changeStatus(
                $contactMessage,
                (string)$this->request->getData('status'),
                $this->actorId(),
            );
            $this->Flash->success(__('Message status updated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $contactMessage->id]);
    }

    /**
     * @param string|null $id Message id.
     * @return \Cake\Http\Response|null
     */
    public function assign(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $contactMessage = $this->ContactMessages->get($this->recordId($id));
        $assigned = (int)$this->request->getData('assigned_to_user_id');
        $this->messages->assign(
            $contactMessage,
            $assigned > 0 ? $assigned : null,
            $this->actorId(),
        );
        $this->Flash->success(__('Assignment updated.'));

        return $this->redirect(['action' => 'view', $contactMessage->id]);
    }

    /**
     * @param string|null $id Message id.
     * @return \Cake\Http\Response|null
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

    /**
     * Staff users that can be assigned an enquiry.
     *
     * @return iterable<\App\Model\Entity\User>
     */
    private function staffOptions(): iterable
    {
        return $this->fetchTable('Users')->find()
            ->matching('UserRoles', function ($query) {
                return $query->where(['UserRoles.revoked_at IS' => null]);
            })
            ->distinct(['Users.id'])
            ->orderBy(['Users.email' => 'ASC'])
            ->all();
    }
}
