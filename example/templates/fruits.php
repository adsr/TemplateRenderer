<? $t->start_block('title'); ?>Fruits<? $t->end_block('title'); ?>

<? $t->start_block('content'); ?>
<p><? $t->e("It's important to eat fruits."); ?></p>
<? if ($show_fruits): ?>
    <p><? $t->e('Here are %d fruits:', count($fruits)); ?></p>
    <ul>
    <? foreach ($fruits as $fruit): ?>
        <li><? $t->render('partial/fruit', ['fruit' => $fruit]); ?></li>
    <? endforeach; ?>
    </ul>
<? endif; ?>
<? $t->end_block('content'); ?>

<? $t->render('layout'); ?>
