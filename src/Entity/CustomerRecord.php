<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_customer,repository=Flexgrid\Modules\AdminCustomer\Repository\CustomerRecordRepository,type=Module,in_menu=false,hide=true,hide=true]
 * @FG\Index::tenant_public[columns={tenantId,publicId},unique=true]
 * @FG\Index::tenant_source_external[columns={tenantId,source,externalId},unique=true]
 * @FG\Index::tenant_display_name[columns={tenantId,displayName}]
 * @FG\Index::tenant_status[columns={tenantId,status}]
 */
final class CustomerRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */ protected $id;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $tenantId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $publicId;
    /** @FG\Column[type=varchar,length=255,required=true] */ protected $displayName;
    /** @FG\Column[type=varchar,length=255] */ protected $companyName;
    /** @FG\Column[type=varchar,length=64] */ protected $registrationNumber;
    /** @FG\Column[type=varchar,length=64] */ protected $taxNumber;
    /** @FG\Column[type=varchar,length=16,required=true] */ protected $status;
    /** @FG\Column[type=text] */ protected $notes;
    /** @FG\Column[type=varchar,length=64] */ protected $source;
    /** @FG\Column[type=varchar,length=128] */ protected $externalId;
    /** @FG\Column[type=bigint,required=true] */ protected $createdAt;
    /** @FG\Column[type=bigint,required=true] */ protected $updatedAt;

    public function getId() { return $this->id; }
    public function setId($value) { $this->id = $value; return $this; }
    public function getTenantId() { return $this->tenantId; }
    public function setTenantId($value) { $this->tenantId = $value; return $this; }
    public function getPublicId() { return $this->publicId; }
    public function setPublicId($value) { $this->publicId = $value; return $this; }
    public function getDisplayName() { return $this->displayName; }
    public function setDisplayName($value) { $this->displayName = $value; return $this; }
    public function getCompanyName() { return $this->companyName; }
    public function setCompanyName($value) { $this->companyName = $value; return $this; }
    public function getRegistrationNumber() { return $this->registrationNumber; }
    public function setRegistrationNumber($value) { $this->registrationNumber = $value; return $this; }
    public function getTaxNumber() { return $this->taxNumber; }
    public function setTaxNumber($value) { $this->taxNumber = $value; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus($value) { $this->status = $value; return $this; }
    public function getNotes() { return $this->notes; }
    public function setNotes($value) { $this->notes = $value; return $this; }
    public function getSource() { return $this->source; }
    public function setSource($value) { $this->source = $value; return $this; }
    public function getExternalId() { return $this->externalId; }
    public function setExternalId($value) { $this->externalId = $value; return $this; }
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
