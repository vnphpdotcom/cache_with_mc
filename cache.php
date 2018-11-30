<?php
	class Cache
	{
		private $mc;
		
		function __construct()
		{
			$this->mc = new Memcache;
			$this->mc->connect('127.0.0.1', 11211);
		}
		
		function set($params,$value,$expire)
		{
			if(is_array($params))
			{
				$namespace = $params['namespace'];
				$class = $params['class'];
				$id = $params['id'];
				$this->map($namespace,$class,$id);
				$this->mc->set($namespace.'_'.$class.'_'.$id,$value,false,$expire);
			}
		}
		
		function get($params)
		{
			if(is_array($params))
			{
				$namespace = $params['namespace'];
				$class = $params['class'];
				$id = $params['id'];
				$data = $this->mc->get($namespace.'_'.$class.'_'.$id);
				return ($data)?$data:$this->find($namespace,$class,$id);
			}else return null;
		}
		
		function update($namespace,$class,$newclass,$expire)
		{
			$data = $this->mc->get($namespace);
			if(isset($data->$class))
			{
				$data->$newclass = $data->$class;
				$data->$class = new stdClass;
				$data->$class->update = $newclass;
				$this->mc->set($namespace,$data,false,$expire);
			}
		}
		
		function find($namespace,$class,$id)
		{
			$data = $this->mc->get($namespace);
			if(isset($data->$class->update))
			{
				$data = $this->mc->get($namespace.'_'.$data->$class->update.'_'.$id);
			} else $data = null;
			return ($data)?$data:'<p><strong>Cache miss</strong>';
		}
		
		function map($namespace,$class,$id)
		{
			$data = (object)$this->mc->get($namespace);
			$data->$class = $id;
			$this->mc->set($namespace,$data,false);
		}
		
		function delete($namespace,$class=null,$id=null)
		{
			if($namespace&&$class&&$id)
			{
				$this->mc->delete($namespace.'_'.$class.'_'.$id,0);
			}
			else
			{
				$data = (object)$this->mc->get($namespace);
				if($data)
				{
					foreach($data as $key=>$value)
					{
						if(!isset($value->update))
						{
							($class)?(($class===$key)?$this->mc->delete($namespace.'_'.$key.'_'.$value,0):false):($this->mc->delete($namespace.'_'.$key.'_'.$value,0));
						}
					}
					(!$class)?$this->mc->delete($namespace,0):false;
				}
			}
		}
		
	}
	$executionStartTime = microtime(true);
	$cache = new Cache;	
	$cache->set(array('namespace'=>'news','class'=>'details','id'=>1),'<p>Detail 1: A man shot and killed a charging grizzly bear in Canada on Monday, only to soon discover that the bear had likely killed his wife and 10-month-old child.',3600);
	$cache->set(array('namespace'=>'news','class'=>'contents','id'=>1),'<p>Content 1: A man shot and killed a charging grizzly bear in Canada on Monday, only to soon discover that the bear had likely killed his wife and 10-month-old child.',3600);
	$cache->set(array('namespace'=>'news','class'=>'details','id'=>2),'<p>Detail 2: A man shot and killed a charging grizzly bear in Canada on Monday, only to soon discover that the bear had likely killed his wife and 10-month-old child.',3600);
	$cache->delete('news','details');
	//$cache->update('news','details','new',3600);
	echo $cache->get(array('namespace'=>'news','class'=>'details','id'=>1));
	echo $cache->get(array('namespace'=>'news','class'=>'details','id'=>2));
	echo $cache->get(array('namespace'=>'news','class'=>'contents','id'=>1));
	$executionEndTime = microtime(true);
	$seconds = $executionEndTime - $executionStartTime;
	echo "<p>This script took $seconds to execute.";