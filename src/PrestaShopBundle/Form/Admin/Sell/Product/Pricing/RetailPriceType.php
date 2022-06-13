<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Form\Admin\Sell\Product\Pricing;

use Currency;
use PrestaShop\PrestaShop\Core\Localization\Locale;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Form\Admin\Type\UnavailableType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class RetailPriceType extends TranslatorAwareType
{
    /**
     * @var Locale
     */
    private $contextLocale;

    /**
     * @var Currency
     */
    private $defaultCurrency;

    /**
     * @var array
     */
    private $taxRuleGroupChoices;

    /**
     * @var array
     */
    private $taxRuleGroupChoicesAttributes;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var bool
     */
    private $taxEnabled;

    /**
     * @var bool
     */
    private $isEcotaxEnabled;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        Locale $contextLocale,
        Currency $defaultCurrency,
        array $taxRuleGroupChoices,
        array $taxRuleGroupChoicesAttributes,
        RouterInterface $router,
        bool $taxEnabled,
        bool $isEcotaxEnabled
    ) {
        parent::__construct($translator, $locales);
        $this->contextLocale = $contextLocale;
        $this->defaultCurrency = $defaultCurrency;
        $this->taxRuleGroupChoices = $taxRuleGroupChoices;
        $this->taxRuleGroupChoicesAttributes = $taxRuleGroupChoicesAttributes;
        $this->router = $router;
        $this->taxEnabled = $taxEnabled;
        $this->isEcotaxEnabled = $isEcotaxEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // @todo we should have DecimalType and MoneyDecimalType it was moved in a separate PR #22162
            ->add('price_tax_excluded', MoneyType::class, [
                'required' => false,
                'label' => $this->trans('Retail price (tax excl.)', 'Admin.Catalog.Feature'),
                'attr' => [
                    'data-display-price-precision' => self::PRESTASHOP_DECIMALS,
                    'data-price-specification' => json_encode($this->contextLocale->getPriceSpecification($this->defaultCurrency->iso_code)->toArray()),
                ],
                'row_attr' => [
                    'class' => 'retail-price-tax-excluded',
                ],
                'currency' => $this->defaultCurrency->iso_code,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                    new PositiveOrZero(),
                ],
                'default_empty_data' => 0.0,
                'modify_all_shops' => true,
            ])
            ->add('tax_rules_group_id', ChoiceType::class, [
                'choices' => $this->taxRuleGroupChoices,
                'required' => false,
                // placeholder false is important to avoid empty option in select input despite required being false
                'placeholder' => false,
                'choice_attr' => $this->taxRuleGroupChoicesAttributes,
                'attr' => [
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                    'data-tax-enabled' => $this->taxEnabled,
                ],
                'row_attr' => [
                    'class' => 'retail-price-tax-rules-group-id',
                ],
                'label' => $this->trans('Tax rule', 'Admin.Catalog.Feature'),
                'help' => !$this->taxEnabled ? $this->trans('Tax feature is disabled, it will not affect price tax included.', 'Admin.Catalog.Feature') : '',
                'external_link' => [
                    'text' => $this->trans('[1]Manage tax rules[/1]', 'Admin.Catalog.Feature'),
                    'href' => $this->router->generate('admin_taxes_index'),
                    'align' => 'right',
                ],
                'modify_all_shops' => true,
            ])
            ->add('price_tax_included', MoneyType::class, [
                'required' => false,
                'label' => $this->trans('Retail price (tax incl.)', 'Admin.Catalog.Feature'),
                'attr' => [
                    'data-display-price-precision' => self::PRESTASHOP_DECIMALS,
                ],
                'row_attr' => [
                    'class' => 'retail-price-tax-included',
                ],
                'currency' => $this->defaultCurrency->iso_code,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                    new PositiveOrZero(),
                ],
                'default_empty_data' => 0.0,
                'modify_all_shops' => true,
            ])
        ;

        if ($this->isEcotaxEnabled) {
            $builder->add('ecotax', UnavailableType::class, [
                'label' => $this->trans('Ecotax (tax incl.)', 'Admin.Catalog.Feature'),
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                    new PositiveOrZero(),
                ],
                'modify_all_shops' => true,
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label' => $this->trans('Retail price', 'Admin.Catalog.Feature'),
            'label_tag_name' => 'h3',
            'required' => false,
            'attr' => [
                'class' => 'retail-price-widget',
            ],
        ]);
    }
}
