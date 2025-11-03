<?php

namespace App\Form;

use App\Entity\Exam;
use App\Entity\Subject;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exam_name')
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'id',
            ])
            ->add('user_taking_exam', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('date')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
        ]);
    }
}
