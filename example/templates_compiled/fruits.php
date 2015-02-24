<? $t->start_block('title'); ?>Fruits<? $t->end_block('title'); ?>

<? $t->start_block('content'); ?>
<p><? $t->e("d25956f11ce1e3b3efc70d99411c0c1a"); ?></p>
<? if ($show_fruits): ?>
    <p><? $t->e('433cb089f1cab4ff85f8497215b1bcac', count($fruits)); ?></p>
    <ul>
    <? foreach ($fruits as $fruit): ?>
        <li><? $t->render('partial/fruit', ['fruit' => $fruit]); ?></li>
    <? endforeach; ?>
    </ul>
<? endif; ?>
<? $t->end_block('content'); ?>

<? $t->render('layout'); ?>
