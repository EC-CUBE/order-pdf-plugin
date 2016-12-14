<?php
/*
 * This file is part of the OrderPdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Entity;

use Eccube\Entity\AbstractEntity;

/**
 * Class OrderPdf.
 */
class OrderPdf extends AbstractEntity
{
    /**
     * @var string
     */
    private $ids;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $issue_date;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message1;

    /**
     * @var string
     */
    private $message2;

    /**
     * @var string
     */
    private $message3;

    /**
     * @var string
     */
    private $note1;

    /**
     * @var string
     */
    private $note2;

    /**
     * @var string
     */
    private $note3;

    /**
     * @var bool
     */
    private $default;

    /**
     * @var int
     */
    private $del_flg;

    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;

    /**
     * Set order id.
     *
     * @param string $ids
     *
     * @return OrderPdf
     */
    public function setIds($ids)
    {
        $this->ids = $ids;

        return $this;
    }

    /**
     * Get order.
     *
     * @return string
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * Set member id.
     *
     * @param string $id
     *
     * @return OrderPdf
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set download date.
     *
     * @param \DateTime $issueDate
     *
     * @return OrderPdf
     */
    public function setIssueDate($issueDate)
    {
        $this->issue_date = $issueDate;

        return $this;
    }

    /**
     * Get download date.
     *
     * @return \DateTime
     */
    public function getIssueDate()
    {
        return $this->issue_date;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return OrderPdf
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get message1.
     *
     * @return string
     */
    public function getMessage1()
    {
        return $this->message1;
    }

    /**
     * Set message1.
     *
     * @param string $message1
     *
     * @return OrderPdf
     */
    public function setMessage1($message1)
    {
        $this->message1 = $message1;

        return $this;
    }

    /**
     * Get message2.
     *
     * @return string
     */
    public function getMessage2()
    {
        return $this->message2;
    }

    /**
     * Set message2.
     *
     * @param string $message2
     *
     * @return OrderPdf
     */
    public function setMessage2($message2)
    {
        $this->message2 = $message2;

        return $this;
    }

    /**
     * Get message3.
     *
     * @return string
     */
    public function getMessage3()
    {
        return $this->message3;
    }

    /**
     * Set message3.
     *
     * @param string $message3
     *
     * @return OrderPdf
     */
    public function setMessage3($message3)
    {
        $this->message3 = $message3;

        return $this;
    }

    /**
     * Get note1.
     *
     * @return string
     */
    public function getNote1()
    {
        return $this->note1;
    }

    /**
     * Set note1.
     *
     * @param string $note1
     *
     * @return OrderPdf
     */
    public function setNote1($note1)
    {
        $this->note1 = $note1;

        return $this;
    }

    /**
     * Get note2.
     *
     * @return string
     */
    public function getNote2()
    {
        return $this->note2;
    }

    /**
     * Set note2.
     *
     * @param string $note2
     *
     * @return OrderPdf
     */
    public function setNote2($note2)
    {
        $this->note2 = $note2;

        return $this;
    }

    /**
     * Get note3.
     *
     * @return string
     */
    public function getNote3()
    {
        return $this->note3;
    }

    /**
     * Set note3.
     *
     * @param string $note3
     *
     * @return OrderPdf
     */
    public function setNote3($note3)
    {
        $this->note3 = $note3;

        return $this;
    }

    /**
     * Set default to save.
     *
     * @param bool $isDefault
     *
     * @return OrderPdf
     */
    public function setDefault($isDefault)
    {
        $this->default = $isDefault;

        return $this;
    }

    /**
     * Get default.
     *
     * @return bool
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set del_flg.
     *
     * @param int $delFlg
     *
     * @return OrderPdf
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * Get del_flg.
     *
     * @return int
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return OrderPdf
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return OrderPdf
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
