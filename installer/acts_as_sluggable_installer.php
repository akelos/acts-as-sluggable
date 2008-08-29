<?php

class ActsAsSluggableInstaller extends AkPluginInstaller
{
    
    
    function down_1()
    {
        echo "Uninstalling the acts_as_taggable plugin migration\n";
    }
    
    function up_1()
    {
        echo "\n\nInstallation completed\n";
    }
    

}
?>