<?php

require_once __DIR__ . '/smarty/libs/Smarty.class.php';

class VBase extends Smarty
{
    public function __construct()
    {
        parent::__construct();

        $baseDirSmarty = __DIR__ . '/smarty';

        // Directory contenente i template .tpl
        $this->setTemplateDir($baseDirSmarty . '/templates');

        // In produzione usa la cartella del progetto; i test possono indicarne una isolata.
        $compileDir = getenv('LUMEA_SMARTY_COMPILE_DIR') ?: $baseDirSmarty . '/templates_c';
        $this->setCompileDir($compileDir);

        // Directory utilizzata per la cache
        $this->setCacheDir($baseDirSmarty . '/cache');

        // Directory per eventuali file di configurazione Smarty
        $this->setConfigDir($baseDirSmarty . '/configs');
    }

    /** Cambia l'URL della risorsa quando cambia il contenuto, evitando CSS o JS obsoleti. */
    protected function versioneRisorsa(string $percorso): string
    {
        return substr(hash_file('sha256', __DIR__ . '/../' . $percorso), 0, 16);
    }

    protected function setTemplate($template)
    {
        $this->display($template);
    }

    protected function setDataIntoTemplate($reference, $data)
    {
        $this->assign($reference, $data);
    }
}
