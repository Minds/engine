<?php
/**
 * Payment Sale Entity
 */
namespace Minds\Core\Payments;

use Minds\Entities\User;

class Sale{

  private $status = "pending";
  private $id;

  private $amount;
  private $fee;
  private $merchant;
  private $customerId;
  private $nonce;

  public function __construct(){

  }

  /**
   * Set the ID of the order
   * @param string $status
   * @return $this;
   */
  public function setId($id){
    $this->id = $id;
    return $this;
  }

  /**
   * Get the ID the order
   * @return string
   */
  public function getId(){
    return $this->id;
  }

  /**
   * Set the status of the order
   * @param string $status
   * @return $this;
   */
  public function setStatus($status){
    $this->status = $status;
    return $this;
  }

  /**
   * Get the status opf the order
   * @return string
   */
  public function getStatus(){
    return $this->status;
  }

  /**
   * Set the amount of the sale
   * @param string $amout
   * @return $this
   */
  public function setAmount($amount){
    $this->amount = $amount;
    return $this;
  }

  public function getAmount(){
    return $this->amount;
  }

  public function setFee($fee){
    $this->fee = $fee;
    return $this;
  }

  public function getFee(){
    return $this->fee;
  }

  public function setMerchant(User $user){
    $this->merchant = $user;
    return $this;
  }

  public function getMerchant(){
    return $this->merchant;
  }

  public function setNonce($nonce){
    $this->nonce = $nonce;
    return $this;
  }

  public function getNonce(){
    return $this->nonce;
  }

  public function setCustomerId($customerId){
    $this->customerId = $customerId;
    return $this;
  }

  public function getCustomerId(){
    return $this->customerId;
  }

  /**
   * Set the time the sale was made
   * @param int $time
   * @return $this
   */
  public function setCreatedAt($time){
    $this->createdAt = $time;
    return $this;
  }

  public function getCreatedAt(){
    return $this->createdAt;
  }

  /**
   * Set the time the sale was settled
   * @param int $time
   * @return $this
   */
  public function setSettledAt($time){
    $this->settledAt = $time;
    return $this;
  }

  public function getSettledAt(){
    return $this->settledAt;
  }

}
