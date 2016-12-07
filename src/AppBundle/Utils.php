<?php

namespace AppBundle;

use Digicol\SchemaOrg\Sdk\AdapterInterface;
use Digicol\SchemaOrg\Sdk\ThingInterface;


class Utils
{
    /**
     * @param array $params
     * @return AdapterInterface
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
    
    
    public function getThingTemplateVars(ThingInterface $thing, array $params = [ ])
    {
        $properties = $thing->getProperties();
        
        $properties[ 'digicol:reconciled' ] = \Digicol\SchemaOrg\Sdk\Utils::reconcileThingProperties
        (
            $thing->getType(),
            $properties
        );
        
        // Some content sources provide multiple thumbnails; find the largest one
        // (to show on the details page)
        
        $thumbnails = [ ];
        
        if (! empty($properties['thumbnail']))
        {
            foreach ($properties['thumbnail'] as $thumbnail)
            {
                $thumbnails[ ] = $thumbnail;
            }
        }
        elseif (isset($properties['associatedMedia']) && is_array($properties['associatedMedia']))
        {
            foreach ($properties['associatedMedia'] as $media)
            {
                if (! isset($media['thumbnail']))
                {
                    continue;
                }

                foreach ($media['thumbnail'] as $thumbnail)
                {
                    $thumbnails[ ] = $thumbnail;
                }
            }
        }

        $properties['digicol:reconciled']['digicol:largestThumbnailUrl'] = $this->getLargestThumbnailUrl
        (
            $thumbnails, 
            $properties['digicol:reconciled']['thumbnailUrl']
        );
        
        return $properties;
    }


    protected function getLargestThumbnailUrl(array $thumbnails, $default_url)
    {
        $result = $default_url;
        
        $url_to_size = [ ];
        
        foreach ($thumbnails as $thumbnail)
        {
            if (empty($thumbnail['contentUrl']) || empty($thumbnail['width']) || empty($thumbnail['height']))
            {
                continue;
            }
            
            $url_to_size[ $thumbnail['contentUrl'] ] = intval($thumbnail['width']) * intval($thumbnail['height']);
        }

        asort($url_to_size);
        
        foreach ($url_to_size as $url => $size)
        {
            $result = $url;
        }
        
        return $result;
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
