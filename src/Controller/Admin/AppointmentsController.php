<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\ServiceRequest;
use App\Service\Services\AppointmentService;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use InvalidArgumentException;

/**
 * Staff scheduling for installation and repair requests.
 */
class AppointmentsController extends AdminController
{
    /**
     * @var \App\Service\Services\AppointmentService
     */
    private AppointmentService $appointments;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->appointments = new AppointmentService();
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $status = (string)$this->request->getQuery('status', '');
        $query = $this->fetchTable('ServiceRequests')->find()
            ->contain(['ServiceTypes', 'Customers', 'ServiceAppointments'])
            ->orderBy(['ServiceRequests.id' => 'DESC']);
        if ($status !== '' && isset(ServiceRequest::statusLabels()[$status])) {
            $query->where(['ServiceRequests.status' => $status]);
        }
        $requests = $this->paginate($query, ['limit' => 20]);
        $this->set(compact('requests', 'status'));
    }

    /**
     * @param string|null $id Request id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $request = $this->fetchTable('ServiceRequests')->get($this->recordId($id), contain: [
            'ServiceTypes',
            'Customers',
            'ServiceAppointments' => ['Users'],
            'ServiceWorkLogs' => ['Users'],
            'ServicePartsUsed' => ['ProductVariants'],
        ]);
        $staff = $this->fetchTable('Users')->find()
            ->where(['role !=' => 'customer', 'status' => 'active', 'deleted IS' => null])
            ->orderBy(['first_name' => 'ASC'])
            ->all();
        $variants = $this->fetchTable('ProductVariants')->find()
            ->contain(['Products'])
            ->where(['ProductVariants.is_active' => true])
            ->orderBy(['Products.name' => 'ASC'])
            ->all();
        $this->set(compact('request', 'staff', 'variants'));
    }

    /**
     * @param string|null $id Request id.
     * @return \Cake\Http\Response|null
     */
    public function schedule(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->requirePermission('services.dispatch');
        $request = $this->fetchTable('ServiceRequests')->get($this->recordId($id));
        try {
            $start = DateTime::parse((string)$this->request->getData('starts_at'), 'Australia/Melbourne')
                ->setTimezone('UTC');
            $end = DateTime::parse((string)$this->request->getData('ends_at'), 'Australia/Melbourne')
                ->setTimezone('UTC');
            $this->appointments->schedule(
                $request,
                (int)$this->request->getData('assigned_staff_user_id'),
                $start,
                $end,
                $this->actorId(),
                $this->nullablePosted('customer_instructions'),
            );
            $this->Flash->success(__('Appointment scheduled.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $request->id]);
    }

    /**
     * @param string|null $id Request id.
     * @return \Cake\Http\Response|null
     */
    public function updateStatus(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $request = $this->fetchTable('ServiceRequests')->get($this->recordId($id));
        try {
            $this->appointments->updateRequestStatus($request, (string)$this->request->getData('status'));
            $this->Flash->success(__('Request status updated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $request->id]);
    }

    /**
     * @param string|null $id Request id.
     * @return \Cake\Http\Response|null
     */
    public function addWorkLog(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $request = $this->fetchTable('ServiceRequests')->get($this->recordId($id), contain: ['ServiceAppointments']);
        $appointmentId = $request->service_appointments[0]->id ?? null;
        try {
            $this->appointments->addWorkLog(
                $request,
                (int)$this->request->getData('staff_user_id') ?: $this->actorId(),
                (string)$this->request->getData('work_summary'),
                (int)$this->request->getData('duration_minutes'),
                $appointmentId ? (int)$appointmentId : null,
            );
            $this->Flash->success(__('Work log saved.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $request->id]);
    }

    /**
     * @param string|null $id Request id.
     * @return \Cake\Http\Response|null
     */
    public function addPart(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $request = $this->fetchTable('ServiceRequests')->get($this->recordId($id));
        try {
            $this->appointments->addPart(
                $request,
                (int)$this->request->getData('product_variant_id'),
                (int)$this->request->getData('quantity'),
                $this->actorId(),
            );
            $this->Flash->success(__('Part recorded.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $request->id]);
    }

    /**
     * @param string $field Posted field.
     * @return string|null
     */
    private function nullablePosted(string $field): ?string
    {
        $value = trim((string)$this->request->getData($field));

        return $value === '' ? null : $value;
    }
}
