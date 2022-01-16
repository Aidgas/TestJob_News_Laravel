<?php

namespace App\Helpers;
/**
 * - class work with file system
 *
 * @version 1.2.6
 */ 
class FileSystem
{
    /**
     * recursive delete files timeout
     */
    static public function recursive_delete_files_timeout($path, $timeout)
    {
        if($path[strlen($path)-1] != DIRECTORY_SEPARATOR)
        { $path.= DIRECTORY_SEPARATOR; }
        
        $files = scandir($path);
        $full_path = "";
        $time = (int)microtime(true);
         
        foreach($files as $v)
        {
            if($v=="." or $v=="..")
            { continue; }
            
            $full_path = $path.$v;
            if(is_file($full_path))
            {
              if( ($time - filectime($full_path)) >= $timeout)
              {
                unlink($full_path);
              }
            }
            else
            {
              self::recursive_delete_files_timeout($path.$v.DIRECTORY_SEPARATOR, $timeout);
            }
        }
    }
    
    /**
     * Рекурсивное удаление директории
     * Если $delete_main_path = true - удалить директории $path
     */
    static public function recursive_delete_dir($path, $delete_main_path = false)
    {
        if(! file_exists($path))
        { return false; }
        
        if($path[strlen($path)-1] != DIRECTORY_SEPARATOR)
        { $path.= DIRECTORY_SEPARATOR; }
        
        $files = scandir($path);
        $full_path = "";
        $time = (int)microtime(true);
         
        foreach($files as $v)
        {
            if($v=="." or $v=="..")
            { continue; }
            
            $full_path = $path.$v;
            if(is_file($full_path))
            { unlink($full_path); }
            else
            {
              self::recursive_delete_dir($path.$v.DIRECTORY_SEPARATOR);
              rmdir($path.$v);
            }
        }
        if($delete_main_path)
        { rmdir($path); }
    }
    
    /**
     * create directory
     */
    static public function create_directory($path, $mode=0755)
    {
        if( !file_exists($path) )
        {
            $oldumask = umask(0);
            @mkdir($path, $mode, true);
            umask($oldumask);
            chmod($path, $mode);
        }
    }
    
    /**
     * create emrty file
     */
    static public function create_empty_file($filename)
    {
        if ( ! is_file($filename))
        {
            $file = fopen($filename, 'w');
            fclose($file);
            return true;
        }
        return false;
    }
    
    /**
     * get array files and dirs in path
     */
    static public function get_dir_list($str_path)
    {
        $arr = array();
        
        if(! is_dir($str_path))
        { return false; }
        
        $d = dir($str_path);
        
        for($i = 0; false !== ($entry = $d->read()); $i++)
        { 
            if($entry != "." && $entry != "..")      
            { array_push($arr,$entry); }
        }
        
        return $arr;
    }
    
    /**
     * get array only files in directory
     */
    static public function list_files_in_path($str_path)
    {
        if(! is_dir($str_path))
        { return false; }
        
        $d = dir($str_path);
        $temp = self::get_dir_list($d);
        $rarr = array();
        foreach($temp as $item)
        {
            if(is_file($strDir.DIRECTORY_SEPARATOR.$item))
            {
                $rarr[] = $item;
            }
        }
        return $rarr;
    }
    
    /**
     * get array only files in directory
     */
    static public function list_dirs_in_path($str_path)
    {
        if(! is_dir($str_path))
        { return false; }
        
        $d = dir($str_path);
        $temp = self::get_dir_list($d);
        $rarr = array();
        foreach($temp as $item)
        {
            if(is_dir($strDir.DIRECTORY_SEPARATOR.$item))
            {
                $rarr[] = $item;
            }
        }
        return $rarr;
    }    
    
    /**
     * @param mixed $filename
     * @param mixed $folder
     * @return
     */
    static public function move_file($filename, $folder, $create_folder = false)
    {
        if ( ! is_file($filename))
        { return false; }
        
        if ( ! is_dir($folder) && $create_folder == false)
        { return false; }
        
        if( ! file_exists($folder) )
        {
            if($create_folder == true)
            { self::create_directory($folder); }
            else
            { return false; }
        }

        if ($folder[strlen($folder) - 1] != DIRECTORY_SEPARATOR)
        { $folder .= DIRECTORY_SEPARATOR; }
        
        $file = $filename;
        if (substr_count($filename, DIRECTORY_SEPARATOR) > 0)
        {
            $file_parts = explode(DIRECTORY_SEPARATOR, $filename);
            $file = $file_parts[count($file_parts) - 1];
        }
        
        copy($filename, $folder . DIRECTORY_SEPARATOR . $file);
        unlink($filename);
    }
    
    
    /**
     * Преобразование размера в байтах в "человеческие" величины. Принимает размер в байтах
     * $size int в байтах
     */
    static public function human_readable_sizebytes( $size )
    {
        $name = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $mysize = $size ? round( $size / pow(1024, ( $i = floor( log( $size, 1024 ) ) ) ), 2) .' ' .$name[$i] : $size.' Bytes';
        return $mysize;
    }    
    /**
     * human readable filesize
     */
    static public function human_readable_filesize($filepath)
    {
        if( ! is_file($filepath))
        { return false; }
        
        $size = filesize($filepath);
        
        return self::human_readable_sizebytes($size);
    }
    
    /**
    * Get a human readable file permission string for a file
    *
    * @static
    * @link 	http://www.php.net/function.fileperms
    * @param	string		$pathtofile
    * @return	string|false	file permission string or false if an invalid $pathtofile was passed
    */
    static public function get_human_readable_file_permissions( $pathtofile )
    {
        if( !file_exists($pathtofile) )
        { return false; }
           
        $perms = fileperms( $pathtofile );
        
            if( ( $perms & 0xC000 ) == 0xC000 ) { $info = 's'; } // Socket
        elseif( ( $perms & 0xA000 ) == 0xA000 ) { $info = 'l'; } // Symbolic Link
        elseif( ( $perms & 0x8000 ) == 0x8000 ) { $info = '-'; } // Regular
        elseif( ( $perms & 0x6000 ) == 0x6000 ) { $info = 'b'; } // Block special
        elseif( ( $perms & 0x4000 ) == 0x4000 ) { $info = 'd'; } // Directory
        elseif( ( $perms & 0x2000 ) == 0x2000 ) { $info = 'c'; } // Character special
        elseif( ( $perms & 0x1000 ) == 0x1000 ) { $info = 'p'; } // FIFO pipe
        else                                    { $info = 'u'; } // Unknown
        
        // Owner
        $info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
        $info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
        $info .= ( ( $perms & 0x0040 ) ?
                    ( ( $perms & 0x0800 ) ? 's' : 'x' ) :
                    ( ( $perms & 0x0800 ) ? 'S' : '-' ) );
        
        // Group
        $info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
        $info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
        $info .= ( ( $perms & 0x0008 ) ?
                    ( ( $perms & 0x0400 ) ? 's' : 'x' ) :
                    ( ( $perms & 0x0400 ) ? 'S' : '-' ) );
   
        // World
        $info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
        $info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
        $info .= ( ( $perms & 0x0001 ) ?
                    ( ( $perms & 0x0200 ) ? 't' : 'x' ) :
                    ( ( $perms & 0x0200 ) ? 'T' : '-' ) );
        
        return $info;
    }
    
    static private function _sort_cmp_from_type($a, $b)
    {
        return strcmp($a['filetype'], $b['filetype']);
    }
    
    static private function _sort_cmp_from_size($a, $b)
    {
        return (int)$a['filesize'] < (int)$b['filesize']?1:-1;
    }    
    
    /**
     * @param $path - full path to folder
     * @param $sort_type - type|size
     * @param $reverse - boolean
     * @return array|false
     */
    static function scan_folder($path, $sort_type = '', $reverse = false)
    {
        if( ! is_dir($path) && ! is_file($path) )
        { return false; }
        
        if(substr($path, -1) != DIRECTORY_SEPARATOR)
        { $path .= DIRECTORY_SEPARATOR; }
        
        $mass_f = scandir($path);
        unset($mass_f[0]);
        unset($mass_f[1]);
        $table_files = array();
        foreach($mass_f as $v)
        {
            $f_size = filesize($path.DIRECTORY_SEPARATOR.$v);
            
            $tmp = array();
            $tmp['filename']     = $v;            
            $tmp['filesize_txt'] = self::human_readable_sizebytes( $f_size );
            $tmp['filetype']     = filetype($path.DIRECTORY_SEPARATOR.$v);
            $tmp['filectime']    = date("D. d.m.Y H:i:s", filectime($path.DIRECTORY_SEPARATOR.$v));
            $tmp['filesize']     = $f_size;
            $tmp['fileperms']    = self::get_human_readable_file_permissions($path.DIRECTORY_SEPARATOR.$v);
            $table_files[] = $tmp;
        }
        
        if($sort_type == 'type')
        {
            usort($table_files, array( __CLASS__, '_sort_cmp_from_type' ));
        }
        else if($sort_type == 'size')
        {
            usort($table_files, array( __CLASS__, '_sort_cmp_from_size' ));
        }
        
        return ($reverse)? array_reverse($table_files):$table_files;
    }
    
    /**
     * @param $path - full path to folder
     * @param $sort_type - type|size
     * @param $reverse - boolean
     * @return array|false
     */
    static function scan_only_folder($path, $sort_type = '', $reverse = false)
    {
        if( ! is_dir($path) && ! is_file($path) )
        { return false; }
        
        if(substr($path, -1) != DIRECTORY_SEPARATOR)
        { $path .= DIRECTORY_SEPARATOR; }
        
        $mass_f = scandir($path);
        unset($mass_f[0]);
        unset($mass_f[1]);
        $table_files = array();
        foreach($mass_f as $v)
        {
            if( ! is_dir($path.DIRECTORY_SEPARATOR.$v) )
            { continue; }
            
            $f_size = filesize($path.DIRECTORY_SEPARATOR.$v);
            
            $tmp = array();
            $tmp['filename']     = $v;            
            $tmp['filesize_txt'] = self::human_readable_sizebytes( $f_size );
            $tmp['filetype']     = filetype($path.DIRECTORY_SEPARATOR.$v);
            $tmp['filectime']    = date("D. d.m.Y H:i:s", filectime($path.DIRECTORY_SEPARATOR.$v));
            $tmp['filesize']     = $f_size;
            $tmp['fileperms']    = self::get_human_readable_file_permissions($path.DIRECTORY_SEPARATOR.$v);
            $table_files[] = $tmp;
        }
        
        if($sort_type == 'type')
        {
            usort($table_files, array( __CLASS__, '_sort_cmp_from_type' ));
        }
        else if($sort_type == 'size')
        {
            usort($table_files, array( __CLASS__, '_sort_cmp_from_size' ));
        }
        
        return ($reverse)? array_reverse($table_files):$table_files;
    }
    
       /**
        * Copy a file, or recursively copy a folder and its contents
        * @param       string   $source    Source path
        * @param       string   $dest      Destination path
        * @param       string   $permissions New folder creation permissions
        * @return      bool     Returns true on success, false on failure
        */
       static  function xcopy($source, $dest, $permissions = 0755)
       {
            // Check for symlinks
            if (is_link($source))
            {
               return symlink(readlink($source), $dest);
            } 
            
            // Simple copy for a file
            if (is_file($source)) {
               return copy($source, $dest);
            }
            
            // Make destination directory
            if (!is_dir($dest))
            {
               FileSystem::create_directory($dest, $permissions);
            }
           
           // Loop through the folder
           $dir = dir($source);
           while (false !== $entry = $dir->read())
           {
                // Skip pointers
                if ($entry == '.' || $entry == '..')
                {
                   continue;
                }
                
                // Deep copy directories
                FileSystem::xcopy("$source/$entry", "$dest/$entry");
           }
       
           // Clean up
           $dir->close();
           return true;
       }
}
