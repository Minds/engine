<?php
/**
 * Payment Merchant Entity
 */
namespace Minds\Core\Payments;

use Minds\Entities\User;

class Merchant
{
    private $id;
    private $guid;

    private $firstName;
    private $lastName;
    private $email;
    private $dob;
    private $ssn;

    private $street;
    private $city;
    private $region;
    private $state;
    private $country;
    private $postCode;

    private $accountNumber;
    private $routingNumber;
    private $destination;

    private $verified = false;

    private $status = "processing";

    public function __construct()
    {
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getGuid()
    {
        return $this->guid;
    }

    public function setGuid($guid)
    {
        $this->guid = $guid;
        return $this;
    }

    public function setFromUser(User $user)
    {
        $this->guid = $user->guid;
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getName()
    {
        return "$this->firstName $this->lastName";
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function getDateOfBirth()
    {
        return $this->dob;
    }

    public function setDateOfBirth($dob)
    {
        $this->dob = $dob;
        return $this;
    }

    public function getSSN()
    {
        return $this->ssn;
    }

    public function setSSN($ssn)
    {
        $this->ssn = $ssn;
        return $this;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getPostCode()
    {
        return $this->postCode;
    }

    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;
        return $this;
    }

    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getRoutingNumber()
    {
        return $this->routingNumber;
    }

    public function setRoutingNumber($routingNumber)
    {
        $this->routingNumber = $routingNumber;
        return $this;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($destination)
    {
        if (!in_array($destination, array('bank', 'email'))) {
            throw new \Exception("$destination is not a valid payout method");
        }
        $this->destination = $destination;
        return $this;
    }

    public function isVerified()
    {
        return (bool) $this->verified;
    }

    public function markAsVerified()
    {
        $this->verified = true;
        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
