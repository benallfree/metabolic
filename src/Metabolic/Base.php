<?php

namespace Metabolic;

use Doctrine\Common\Inflector\Inflector;

class Base
{
  static $cache = array();
  
  static function type()
  {
    $type = Inflector::tableize(get_called_class());
    return $type;
  }
    
  static function build_meta_query($args)
  {
    $mq = array();
    foreach($args as $k=>$v)
    {
      $mq[] = array(
       'key' => $k,
       'value' => $v,
       'compare' => '=',
      );
    }
    return $mq;
  }
  
  static function create_or_update($find_args, $update_args = array())
  {
    $obj = static::find_or_create($find_args);
    $args = array_merge($find_args, $update_args);
    $obj->update($args);
    return $obj;
  }
  
  static function create($params)
  {
    $args = array(
      'post_type'=>static::type(),
      'post_status'=>'publish',
    );
    $post_id = wp_insert_post($args);
    $class = get_called_class();
    $obj = new $class($post_id);
    $obj->update($params);
    return $obj;
  }
  
  static $find_index = array();
  
  static function key($array) 
  {
    $out = array(get_called_class());
    $array = array_change_key_case($array);
    $keys = array_keys($array);
    sort($keys);
    foreach($keys as $k)
    {
      $out[] = join('=', array($k, strtolower($array[$k])));
    } 
    $out = join('|',$out);
    return $out;
  }
  
  static function find($params)
  {
    $params = array_change_key_case($params);
    $key = self::key($params);
    if(isset(self::$find_index[$key]))
    {
      return self::$find_index[$key];
    }
    $args = array(
      'post_type'=>static::type(),
    );
    $params = array_change_key_case($params);
    if(isset($params['id']))
    {
      $args['p'] = $params['id'];
      unset($params['id']);
    }
    $args['meta_query'] = self::build_meta_query($params);
    $recs = get_posts($args);
    if(count($recs)==0)
    {
      return null;
    } else {
      $class = get_called_class();
      $obj = new $class($recs[0]);
    }
    self::$find_index[$key] = $obj;
    self::$find_index[self::key(array('id'=>$obj->ID))] = $obj;
    return $obj;
  }
  
  static function find_or_create($args)
  {
    $obj = self::find($args);
    if(!$obj)
    {
      $obj = static::create($args);
    }
    return $obj;
  } 
  
  static function __callstatic($name, $args)
  {
    if(preg_match('/find_by_(.*)/', $name, $matches))
    {
      $params = array($matches[1]=>$args[0]);
      return static::find($params);
    }
  }
}
