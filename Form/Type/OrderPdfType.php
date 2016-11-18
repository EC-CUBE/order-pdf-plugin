<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Form\Type;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class OrderPdfType.
 */
class OrderPdfType extends AbstractType
{
    /**
     * @var Application
     */
    private $app;

    /**
     * OrderPdfType constructor.
     *
     * @param object $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Build config type form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
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
                    new Assert\NotBlank(),
                ),
            ))
            ->add('issue_date', 'date', array(
                'label' => '発行日',
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'required' => true,
                'empty_data' => new \DateTime(),
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\DateTime(),
                ),
            ))
            ->add('title', 'text', array(
                'label' => '帳票タイトル',
                'required' => false,
                'attr' => array('maxlength' => $config['stext_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            // メッセージ
            ->add('message1', 'text', array(
                'label' => '1行目',
                'required' => false,
                'attr' => array('maxlength' => $config['order_pdf_message_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['order_pdf_message_len'])),
                ),
                'trim' => false,
            ))
            ->add('message2', 'text', array(
                'label' => '2行目',
                'required' => false,
                'attr' => array('maxlength' => $config['order_pdf_message_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['order_pdf_message_len'])),
                ),
                'trim' => false,
            ))
            ->add('message3', 'text', array(
                'label' => '3行目',
                'required' => false,
                'attr' => array('maxlength' => $config['order_pdf_message_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['order_pdf_message_len'])),
                ),
                'trim' => false,
            ))
            // 備考
            ->add('note1', 'text', array(
                'label' => '1行目',
                'required' => false,
                'attr' => array('maxlength' => $config['stext_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->add('note2', 'text', array(
                'label' => '2行目',
                'required' => false,
                'attr' => array('maxlength' => $config['stext_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->add('note3', 'text', array(
                'label' => '3行目',
                'required' => false,
                'attr' => array('maxlength' => $config['stext_len']),
                'constraints' => array(
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))
            ->add('default', 'choice', array(
                'choices'  => array(
                    'Yes' => true,
                    'No' => false,
                ),
                // *this line is important*
                'choices_as_values' => true,
                'expanded' => true,
                'multiple' => false,
            ));
    }

    /**
     * Get name method (form factory name).
     *
     * @return string
     */
    public function getName()
    {
        return 'admin_order_pdf';
    }
}
