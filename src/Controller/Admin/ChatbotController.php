<?php
// src/Controller/Admin/ChatbotController.php
declare(strict_types=1);

namespace App\Controller\Admin;

class ChatbotController extends AdminController
{
    /**
     * List chatbot FAQ entries.
     *
     * @return void
     */
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('chatbot.manage');

        $faqs = $this->fetchTable('ChatbotFaq')
            ->find()
            ->orderBy([
                'SortOrder' => 'ASC',
                'FaqId' => 'ASC',
            ])
            ->all();

        $this->set(compact('faqs'));
    }

    /**
     * Create a new FAQ entry.
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('chatbot.manage');

        $table = $this->fetchTable('ChatbotFaq');
        $faq = $table->newEmptyEntity();

        if ($this->request->is('post')) {
            $faq = $table->patchEntity($faq, $this->request->getData());

            if ($table->save($faq)) {
                $this->Flash->success('FAQ saved.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The FAQ could not be saved.');
        }

        $this->set(compact('faq'));
    }

    /**
     * Edit an existing FAQ entry.
     *
     * @param string|int $id FAQ identifier.
     * @return \Cake\Http\Response|null
     */
    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('chatbot.manage');

        $table = $this->fetchTable('ChatbotFaq');
        $faq = $table->get((int)$id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $faq = $table->patchEntity($faq, $this->request->getData());

            if ($table->save($faq)) {
                $this->Flash->success('FAQ updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The FAQ could not be saved.');
        }

        $this->set(compact('faq'));
    }

    /**
     * Delete a FAQ entry.
     *
     * @param string|int $id FAQ identifier.
     * @return \Cake\Http\Response|null
     */
    public function delete(int|string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('chatbot.manage');

        $table = $this->fetchTable('ChatbotFaq');
        $faq = $table->get((int)$id);

        if ($table->delete($faq)) {
            $this->Flash->success('FAQ deleted.');
        } else {
            $this->Flash->error('The FAQ could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Toggle FAQ visibility.
     *
     * @param string|int $id FAQ identifier.
     * @return \Cake\Http\Response|null
     */
    public function toggle(int|string $id)
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('chatbot.manage');

        $table = $this->fetchTable('ChatbotFaq');
        $faq = $table->get((int)$id);

        $faq->IsActive = (int)!((bool)$faq->IsActive);

        $table->save($faq);

        $this->Flash->success('FAQ visibility updated.');

        return $this->redirect(['action' => 'index']);
    }
}
