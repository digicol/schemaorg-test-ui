<?php

namespace AppBundle;


class Utils
{
    /**
     * @param array $params
     * @return \Digicol\SchemaOrg\AdapterInterface
     */
    public function newContentSource(array $params)
    {
        $classname = $params[ 'classname' ];

        if ((! empty($params[ 'autoload_prefix' ])) && (! empty($params[ 'autoload_basedir' ])))
        {
            $this->registerAutoloadPrefix($params[ 'autoload_prefix' ], $params[ 'autoload_basedir' ]);
        }

        if (! empty($params[ 'autoload_file' ]))
        {
            $this->registerAutoloadFile($params[ 'autoload_file' ]);
        }
        
        return new $classname($params);
    }
    
    
    public function getThingTemplateVars(\Digicol\SchemaOrg\ThingInterface $thing, array $params = [ ])
    {
        $type = $thing->getType();
        $properties = $thing->getProperties();

        return
            [
                'type' => $type,
                'properties' => $properties,
                'reconciled' => $thing->getReconciledProperties()
            ];
    }


    protected function registerAutoloadPrefix($prefix, $base_dir)
    {
        // Taken from http://www.php-fig.org/psr/psr-4/examples/
        
        spl_autoload_register(function ($class) use ($prefix, $base_dir) 
        {
            // does the class use the namespace prefix?
            
            $len = strlen($prefix);
            
            if (strncmp($prefix, $class, $len) !== 0) 
            {
                // no, move to the next registered autoloader
                return;
            }

            // get the relative class name
            $relative_class = substr($class, $len);

            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            // if the file exists, require it
            if (file_exists($file)) 
            {
                require $file;
            }
        });
    }
    
    
    public function registerAutoloadFile($autoload_file)
    {
        if (file_exists($autoload_file))
        {
            require_once $autoload_file;
        }
    }
}
