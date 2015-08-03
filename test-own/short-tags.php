<?
echo 'That was a short <? tag; whether it works depends on the php.ini setting short_open_tag. If it does not work, then the PHP interpreter will output the tag itself as well as the echo statement here, since it will ignore the tag and treat it as HTML code. If it does work, then only this text will be output, but not the surrounding tags or the echo statement.';
?>

<?=
'That was a short <?= tag, which is equivalent to <? echo. As opposed to <?, this one is always available, independently of the short_open_tag setting.'
?>
