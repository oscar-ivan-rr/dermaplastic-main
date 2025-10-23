<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
    // error_reporting(-1);

    require_once(DOL_DOCUMENT_ROOT.'/cfdimx/core/modules/modCFDIMX.class.php');

    global $db, $conf;

    $objMod = new modCFDIMX($db);

    $changelog = $objMod->getChangeLog();
    print '<div class="moduledesclong">'.$changelog.'<div>';
