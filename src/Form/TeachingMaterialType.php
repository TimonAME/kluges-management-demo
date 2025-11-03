<?php

namespace App\Form;

use App\Entity\Subject;
use App\Entity\TeachingMaterial;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeachingMaterialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('file_path')
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'id',
            ])
            ->add('teaching_user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TeachingMaterial::class,
        ]);
    }
}
