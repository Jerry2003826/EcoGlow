<?php
declare(strict_types=1);

namespace App\Service\Services;

use App\Model\Entity\Customer;
use App\Model\Entity\ServiceRequest;
use App\Service\AustralianStates;
use App\Service\Inventory\InventoryLedger;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * Customer installation and repair requests. Independent of checkout.
 */
class ServiceBookingService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Service\Inventory\InventoryLedger $ledger Document numbers.
     */
    public function __construct(private InventoryLedger $ledger)
    {
    }

    /**
     * @param \App\Model\Entity\Customer $customer Signed-in customer.
     * @param array<string, mixed> $data Posted booking fields.
     * @return \App\Model\Entity\ServiceRequest
     */
    public function create(Customer $customer, array $data): ServiceRequest
    {
        $typeId = (int)($data['service_type_id'] ?? 0);
        $type = $this->fetchTable('ServiceTypes')->find()
            ->where(['id' => $typeId, 'is_active' => true])
            ->first();
        if ($type === null) {
            throw new InvalidArgumentException('Please choose a service type.');
        }

        $name = trim((string)($data['contact_name'] ?? ''));
        $line1 = trim((string)($data['address_line1'] ?? ''));
        $suburb = trim((string)($data['suburb'] ?? ''));
        $state = strtoupper(trim((string)($data['state'] ?? '')));
        $postcode = trim((string)($data['postcode'] ?? ''));
        $description = trim((string)($data['issue_description'] ?? ''));
        $window = (string)($data['preferred_window'] ?? '');
        $preferredDate = trim((string)($data['preferred_date'] ?? ''));

        if ($name === '' || $line1 === '' || $suburb === '' || $description === '') {
            throw new InvalidArgumentException('Please complete the booking form.');
        }
        if (!AustralianStates::isValid($state) || !preg_match('/^\d{4}$/', $postcode)) {
            throw new InvalidArgumentException('Please enter a valid Australian address.');
        }
        if (!BookingWindows::isValid($window)) {
            throw new InvalidArgumentException('Please choose a preferred time of day.');
        }

        $body = 'Preferred time: ' . BookingWindows::label($window) . "\n\n" . $description;

        $requests = $this->fetchTable('ServiceRequests');
        $request = $requests->newEmptyEntity();
        $request->set('request_number', $this->ledger->nextDocumentNumber('service_request', 'SRV'));
        $request->set('customer_id', $customer->id);
        $request->set('service_type_id', $type->id);
        $request->set('contact_name', $name);
        $request->set('contact_email', $customer->email);
        $request->set('contact_phone', $this->nullable((string)($data['contact_phone'] ?? $customer->phone ?? '')));
        $request->set('address_line1', $line1);
        $request->set('address_line2', $this->nullable((string)($data['address_line2'] ?? '')));
        $request->set('suburb', $suburb);
        $request->set('state', $state);
        $request->set('postcode', $postcode);
        $request->set('country_code', 'AU');
        $request->set('preferred_date', $preferredDate !== '' ? Date::parse($preferredDate) : null);
        $request->set('issue_description', $body);
        $request->set('attachment_urls', []);
        $request->set('priority', 'normal');
        $request->set('status', ServiceRequest::STATUS_NEW);

        return $requests->saveOrFail($request);
    }

    /**
     * @param string $value Posted string.
     * @return string|null
     */
    private function nullable(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
