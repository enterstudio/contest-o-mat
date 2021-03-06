<?php

namespace Application\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntryMetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('key', 'text');
        $builder->add('value', 'textarea');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Application\Entity\EntryMetaEntity',
            'validation_groups' => array('newAndEdit'),
            'csrf_protection' => true,
            'csrf_field_name' => 'csrf_token',
        ));
    }

    public function getName()
    {
        return 'entryMeta';
    }
}
