<?php

namespace Metabolic;

use Doctrine\Common\Inflector\Inflector;

class Base
{
  function __construct($post_obj)
  {
    if(is_numeric($post_obj))
    {
      $post_obj = get_post($post_obj);
    }
    $this->ID = $post_obj->ID;
    $this->obj = $post_obj;
  }
  
  function __get($name)
  {
    if(!$this->obj) $this->obj = get_post($id);
    if(isset($this->obj->$name)) return $this->obj->$name;
    return $this->$name = get_post_meta($this->ID, $name, true);
  }

  function update($args)
  {
    foreach($args as $k=>$v)
    {
      $this->$k = $v;
    }
    $this->save();
  }
  
  function save()
  {
    wp_update_post(array(
      'ID'=>$this->obj->ID,
      'post_title'=>$this->title(),
    ));
    foreach($this->fields as $k)
    {
      if(!isset($this->$k)) continue;
      update_post_meta($this->ID, $k, $this->$k);
    }
  }

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
    $ret = self::find_all($params);
    return array_shift($ret);
  }
  
  static function find_all($params)
  {
    if(is_numeric($params))
    {
      $params = [
        'id'=>$params,
      ];
    }
    if(is_object($params))
    {
      $params = [
        'id'=>$params->ID,
      ];
    }
    $params = array_change_key_case($params);
    $key = self::key($params);
    if(isset(self::$find_index[$key]))
    {
      return self::$find_index[$key];
    }
    $args = array(
      'post_type'=>static::type(),
      'posts_per_page'=>-1,
    );
    $params = array_change_key_case($params);
    if(isset($params['id']))
    {
      $args['p'] = $params['id'];
      unset($params['id']);
    }
    if(count($params))
    {
      $args['meta_query'] = self::build_meta_query($params);
    }
    $recs = get_posts($args);
    $ret = [];
    $class = get_called_class();
    foreach($recs as $r)
    {
      $ret[] = $obj = new $class($r);
      self::$find_index[self::key(array('id'=>$obj->ID))] = $obj;
    }
    self::$find_index[$key] = $ret;
    return $ret;
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
  
  function permalink()
  {
    return get_permalink($this->obj->ID);
  }
}
