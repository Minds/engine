<?php
/**
 * Stripe Connect Account
 */
namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method Account setId(): Account
 * @method Account getDateOfBirth(): string
 * @method Account getCountry(): string
 * @method Account getFirstName(): string
 * @method Account getLastName(): string
 * @method Account getCity(): string
 * @method Account getStreet(): string
 * @method Account getPostCode(): string
 * @method Account getState(): string
 * @method Account getDateOfBirth(): string
 * @method Account getIp(): string
 * @method Account getGender(): string
 * @method Account getPhoneNumber(): string
 * @method Account getSSN(): string
 * @method Account getPersonalIdNumber(): string
 * @method Account getUser(): User
 * @method Account getPayoutInterval(): string
 * @method string getEmail()
 * @method Account setEmail(string $email)
 * @method string getUrl()
 * @method Account setUrl(string $url)
 * @method array getMetadata()
 * @method Account setMetdata(array $metadata)
 * @method Balance getTotalPaidOut()
 * @method Account setTotalPaidOut(Balance $totalPaidOut)
 */
class Account
{
    use MagicAttributes;

    /** @var string $id */
    private $id;

    /** @var User */
    private $user;

    /** @var string $userGuid */
    private $userGuid;

    /** @var string $gener */
    private $gender;

    /** @var string $firstName */
    private $firstName;

    /** @var string $lastName */
    private $lastName;

    /** @var string $email */
    private $email;

    /** @var string $url */
    private $url;

    /** @var string $dob */
    private $dateOfBirth;

    /** @var string $ssn */
    private $sSN;

    /** @var string $personalIdNumber */
    private $personalIdNumber;

    /** @var string $street */
    private $street;

    /** @var string $city */
    private $city;

    /** @var string $region */
    private $region;

    /** @var string $state */
    private $state;

    /** @var string $country */
    private $country;

    /** @var string $postCode */
    private $postCode;

    /** @var string $phoneNumber */
    private $phoneNumber;

    /** @var string $bankAccount */
    private $bankAccount;

    /** @var int $accountNumber */
    private $accountNumber;

    /** @var int $routingNumber */
    private $routingNumber;

    /** @var string $destination */
    private $destination;

    /** @var boolean $verified */
    private $verified = false;

    /** @var string $status */
    private $status = "processing";

    /** @var Balance $totalBalance */
    private $totalBalance;

    /** @var Balance $pendingBalance */
    private $pendingBalance;

    /** @var Balance $totalPaidOut */
    private $totalPaidOut;

    /** @var string $payoutInterval */
    private $payoutInterval;

    /** @var int $payoutDelay */
    private $payoutDelay;

    /** @var int $payoutAnchor */
    private $payoutAnchor;

    /** @var string $requirement */
    private $requirement;

    /** @var string */
    private $ip;

    /** @var array */
    private $metadata;

    /** @var array $exportable */
    private $exportable = [
        'id',
        'guid',
        'gender',
        'firstName',
        'lastName',
        'email',
        'dob',
        'ssn',
        'personalIdNumber',
        'street',
        'city',
        'region',
        'state',
        'country',
        'postCode',
        'phoneNumber',
        'accountNumber',
        'routingNumber',
        'destination',
        'status',
        'verified',
        'bankAccount',
        'payoutInterval',
        'payoutDelay',
        'payoutAnchor',
        'requirement',
    ];

    /**
     * Conditions on setting $destination
     * @param string $destination
     * @return $this
     */
    public function setDestination(string $destination) : Account
    {
        if (!in_array($destination, ['bank', 'email'], true)) {
            throw new \Exception("$destination is not a valid payout method");
        }
        $this->destination = $destination;
        return $this;
    }

    /**
     * Expose to public API
     * @return array
     */
    public function export(array $extend = []) : array
    {
        $export = [];

        foreach ($this->exportable as $field) {
            $export[$field] = $this->$field;
        }

        if ($this->totalBalance) {
            $export['totalBalance'] = $this->totalBalance->export();
        }

        if ($this->pendingBalance) {
            $export['pendingBalance'] = $this->pendingBalance->export();
        }

        if ($this->totalPaidOut) {
            $export['totalPaidOut'] = $this->totalPaidOut->export();
        }

        return $export;
    }
}
