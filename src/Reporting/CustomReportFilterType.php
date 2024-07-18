<?php

namespace App\Reporting;

use App\Form\Type\CustomerType;
use App\Form\Type\MonthPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Form\Type\UserType;
use App\Entity\User;


class CustomReportFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('customer', CustomerType::class, [
            'required' => false,
            'width' => false,
        ]);

        $builder->add('month', MonthPickerType::class, [
            'required' => true,
            'label' => false,
            'view_timezone' => $options['timezone'],
            'model_timezone' => $options['timezone'],
        ]);

        $builder->add('user', UserType::class, [
            'class' => User::class,
            'required' => false,
            'width' => false,
            'include_current_user_if_system_account' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomReportQuery::class,
            'timezone' => date_default_timezone_get(),
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
