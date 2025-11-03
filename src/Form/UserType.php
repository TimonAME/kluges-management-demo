<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Location;
use App\Entity\Todo;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('first_name')
            ->add('last_name')
            ->add('learning_level')
            ->add('birthday', null, [
                'widget' => 'single_text',
            ])
            ->add('guardian', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('private_location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'id',
            ])
            ->add('todos', EntityType::class, [
                'class' => Todo::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('appointments', EntityType::class, [
                'class' => Appointment::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
