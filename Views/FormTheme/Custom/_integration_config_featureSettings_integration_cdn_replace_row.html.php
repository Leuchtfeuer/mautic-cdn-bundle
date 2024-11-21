<?php
/**
 * This is a copy of app/bundles/CoreBundle/Views/FormTheme/Custom/sortablelist_row.html.php.
 *
 * @var \Symfony\Component\Form\FormView               $form
 * @var \Mautic\CoreBundle\Templating\Engine\PhpEngine $view
 * @var bool                                           $isSortable
 * @var string|null                                    $label
 * @var string                                         $addValueButton
 */
$list            = $form->children['list'];
/** @var \Mautic\CoreBundle\Templating\Helper\FormHelper $formHelper */
$formHelper      = $view['form'];
$parentHasErrors = $formHelper->containsErrors($form->parent);

if ($parentHasErrors && empty($list->vars['value']) && isset($form->parent->children['properties']['list']) && null === $form->parent->vars['data']->getId()) {
    // Work around for Symfony bug not repopulating values only for add action
    $list = $form->parent->children['properties']['list'];
}
$hasErrors     = $formHelper->containsErrors($list);
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
$datePrototype = (isset($list->vars['prototype'])) ?
    $view->escape('<div class="sortable">'.$formHelper->widget($list->vars['prototype']).'</div>') : '';

?>
<div class="row">
    <div data-toggle="sortablelist" data-prefix="<?php echo $form->vars['id']; ?>" class="form-group col-xs-12 <?php echo $feedbackClass; ?>" id="<?php echo $form->vars['id']; ?>_list" style="overflow:auto">
        <?php echo $formHelper->label($form, $label); ?>
        <?php echo $formHelper->block($list, 'sortablelist_errors'); ?>
        <div class="help-block">
            <?php echo $formHelper->help($form); ?>
        </div>
        <?php if ($isSortable): ?>
        <div id="sortable-<?php echo $form->vars['id']; ?>" class="list-sortable" <?php foreach ($attr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
            <?php endif; ?>
            <?php foreach ($list->children as $key => $item): ?>
                <?php echo $formHelper->block($item, 'sortablelist_entry_row'); ?>
            <?php endforeach; ?>
        </div>
        <div class="pt-sm">
            <a data-prototype="<?php echo $datePrototype; ?>"
               class="btn btn-warning btn-xs btn-add-item" href="#" id="<?php echo $form->vars['id']; ?>_additem">
                <?php echo $view['translator']->trans($addValueButton); ?>
            </a>
        </div>
        <?php if ($isSortable): ?>
            <input type="hidden" class="sortable-itemcount" id="<?php echo $form->vars['id']; ?>_itemcount" value="<?php echo count($list); ?>" />
        <?php endif; ?>
    </div>
</div>
