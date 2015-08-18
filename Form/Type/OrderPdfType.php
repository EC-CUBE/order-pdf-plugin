<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\OrderPdf\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderPdfType extends AbstractType
{

    private $app;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->app['config'];

        $builder
            ->add('ids', 'text', array(
                'label' => '注文番号',
                'required' => false,
                'attr' => array('readonly' => 'readonly'),
                'constraints' => array(
                    new Assert\NotBlank()
                ),
            ))
            ->add('issue_date', 'date', array(
                'label' => '発行日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'required' => true,
                'data' => new \DateTime(),
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\DateTime(),
                ),

            ))
            ->add('title', 'text', array(
                'label' => '帳票タイトル',
                'required' => false,
                'data' => 'お買上げ明細書(納品書)',
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            // メッセージ
            ->add('message1', 'text', array(
                'label' => '1行目',
                'required' => false,
                'data' => 'このたびはお買上げいただきありがとうございます。',
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
                'trim' => false,
            ))
            ->add('message2', 'text', array(
                'label' => '2行目',
                'required' => false,
                'data' => '下記の内容にて納品させていただきます。',
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
                'trim' => false,
            ))
            ->add('message3', 'text', array(
                'label' => '3行目',
                'required' => false,
                'data' => 'ご確認くださいますよう、お願いいたします。',
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
                'trim' => false,
            ))
            // 備考
            ->add('note1', 'text', array(
                'label' => '1行目',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->add('note2', 'text', array(
                'label' => '2行目',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->add('note3', 'text', array(
                'label' => '3行目',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function getName()
    {
        return 'admin_order_pdf';
    }
}
