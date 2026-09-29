<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_customer_address,repository=Flexgrid\Modules\AdminCustomer\Repository\AddressRecordRepository,type=Module,in_menu=false]
 * @FG\Index::tenant_public[columns={tenantId,publicId},unique=true]
 * @FG\Index::tenant_customer_type_position[columns={tenantId,customerId,addressType,position},unique=true]
 * @FG\Index::tenant_customer[columns={tenantId,customerId}]
 * @FG\Index::tenant_city[columns={tenantId,city}]
 */
final class AddressRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */ protected $id;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $tenantId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $publicId;
    /** @FG\Column[type=int,required=true] */ protected $customerId;
    /** @FG\Column[type=varchar,length=16,required=true] */ protected $addressType;
    /** @FG\Column[type=varchar,length=128] */ protected $label;
    /** @FG\Column[type=varchar,length=255] */ protected $addressee;
    /** @FG\Column[type=varchar,length=255,required=true] */ protected $line1;
    /** @FG\Column[type=varchar,length=255] */ protected $line2;
    /** @FG\Column[type=varchar,length=32,required=true] */ protected $postalCode;
    /** @FG\Column[type=varchar,length=128,required=true] */ protected $city;
    /** @FG\Column[type=varchar,length=128] */ protected $region;
    /** @FG\Column[type=varchar,length=2,required=true] */ protected $countryCode;
    /** @FG\Column[type=tinyint,required=true] */ protected $isPrimary;
    /** @FG\Column[type=int,required=true] */ protected $position;
    /** @FG\Column[type=bigint,required=true] */ protected $createdAt;
    /** @FG\Column[type=bigint,required=true] */ protected $updatedAt;

    public function getId() { return $this->id; }
    public function setId($value) { $this->id = $value; return $this; }
    public function getTenantId() { return $this->tenantId; }
    public function setTenantId($value) { $this->tenantId = $value; return $this; }
    public function getPublicId() { return $this->publicId; }
    public function setPublicId($value) { $this->publicId = $value; return $this; }
    public function getCustomerId() { return $this->customerId; }
    public function setCustomerId($value) { $this->customerId = $value; return $this; }
    public function getAddressType() { return $this->addressType; }
    public function setAddressType($value) { $this->addressType = $value; return $this; }
    public function getLabel() { return $this->label; }
    public function setLabel($value) { $this->label = $value; return $this; }
    public function getAddressee() { return $this->addressee; }
    public function setAddressee($value) { $this->addressee = $value; return $this; }
    public function getLine1() { return $this->line1; }
    public function setLine1($value) { $this->line1 = $value; return $this; }
    public function getLine2() { return $this->line2; }
    public function setLine2($value) { $this->line2 = $value; return $this; }
    public function getPostalCode() { return $this->postalCode; }
    public function setPostalCode($value) { $this->postalCode = $value; return $this; }
    public function getCity() { return $this->city; }
    public function setCity($value) { $this->city = $value; return $this; }
    public function getRegion() { return $this->region; }
    public function setRegion($value) { $this->region = $value; return $this; }
    public function getCountryCode() { return $this->countryCode; }
    public function setCountryCode($value) { $this->countryCode = $value; return $this; }
    public function getIsPrimary() { return $this->isPrimary; }
    public function setIsPrimary($value) { $this->isPrimary = $value; return $this; }
    public function getPosition() { return $this->position; }
    public function setPosition($value) { $this->position = $value; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($value) { $this->createdAt = $value; return $this; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt($value) { $this->updatedAt = $value; return $this; }


    // --- Auto-generated getters and setters ---

    /**
     * Get the value of makeTime
     */
    public function getMakeTime()
    {
        return $this->makeTime;
    }

    /**
     * Set the value of makeTime
     *
     * @param mixed $value
     * @return $this
     */
    public function setMakeTime($value)
    {
        $this->makeTime = $value;
        return $this;
    }

}
