<?php

class RSS
{
	
	//Required:
	public $title;
	public $link;
	public $description;
	
	//Optional:
	public $language = 'en';
	public $copyright;
	public $managingEditor;
	public $webMaster;
	public $pubDate;
	public $lastBuildDate;
	public $categories = array();
	public $generator = 'BentoBase';
	public $docs = 'http://cyber.law.harvard.edu/rss/rss.html';
	public $cloud; // RSSCloud
	public $ttl;
//	public $rating;
//	public $textInput;

	private $image;

	public $skipHours = array();
	public $skipDays = array();
	
	protected $items = array();
	
	public function __construct($title, $description, $link)
	{
		$this->title = $title;
		$this->description = $description;
		$this->link = $link;
		
		
		$this->lastBuildDate = date(DATE_RFC822, time());
		
		
	}
	
	public function add_item($title, $description, $link = '')
	{
		$item = new RSSItem($title, $description, $link);
		$this->items[] = $item;
		return $item;
	}
	
	public function add_cloud($domain, $path, $procedure, $protocol = 'xml-rpc', $port = 80)
	{
		$this->cloud = new RSSCloud($domain, $path, $procedure, $protocol, $port);
	}
	
	public function add_image($title, $url, $link = '')
	{
		$this->image = new RSSImage($title, $url, $link);
		return $this->image;
	}
	
	public function add_category($name, $link = '')
	{
		$this->categories[$name] = ($link) ? $link : '1';
	}
	
	public function make_output()
	{
		$output = '<?xml version="1.0"?>
<rss version="2.0">
   <channel>';
		
		$output .= '
      <title>'. $this->title .'</title>
      <link>'. $this->link .'</link>
      <description>'. $this->description .'</description>';
		
		if(isset($this->language)) $output .= '
      <language>'. $this->language .'</language>';
		
		if(isset($this->copyright)) $output .= '
      <copyright>'. $this->copyright .'</copyright>';
		
		if(isset($this->managingEditor)) $output .= '
      <managingEditor>'. $this->managingEditor .'</managingEditor>';
		
		if(isset($this->webMaster)) $output .= '
      <webMaster>'. $this->webMaster .'</webMaster>';
		
		if(isset($this->pubDate)) $output .= '
      <pubDate>'. date(DATE_RFC822, $this->pubDate) .'</pubDate>';	
		
		if(isset($this->lastBuildDate)) $output .= '
      <lastBuildDate>'. $this->lastBuildDate .'</lastBuildDate>';	

		if(isset($this->generator)) $output .= '
      <generator>'. $this->generator .'</generator>';	

		if(isset($this->docs)) $output .= '
      <docs>'. $this->docs .'</docs>';
		
		if(is_int($this->ttl)) $output .= '
      <ttl>'. $this->ttl .'</ttl>';
		
		if($this->image instanceof RSSImage)
			$output .= $this->image->make_output();
		
		
		$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		foreach($this->skipHours as $hour)
		{
			if($hour >=0 && $hour < 24)
				$hour_output .= '<hour>' . $hour . '</hour>';
		}
		
		
		if(strlen($hour_output) > 0)
			$output .= '
      <skipHours>
         ' . $hour_output . '
      </skipHours';
			
			
		
		foreach($this->skipDays as $day)
		{
			if(in_array($day, $days))
				$day_output .= '<day>' . $day . '</day>';
		}		
		
		if(strlen($day_output) > 0)
			$output .= '
      <skipDays>
         ' . $day_output . '
      </skipDays';
		
		
		
		foreach($this->categories as $name => $link)
		{
			$link = ($link == 1) ? '' : 'domain="' . $link . '"';
			$output .= '
      <category' . $link . '>' . $name .'</category>';
		}		
		
		// public $cloud;
		
		if($this->cloud instanceof RSSCloud) 
			$output .= $this->cloud->make_output();
		
		foreach($this->items as $item)
		{
			$output .= $item->make_output();
		}		
		
		$output .= '   </channel>
</rss>';
		
		return $output;
		
	}
	
}

class RSSItem
{
	public $title;
	public $link;
	public $description;
	public $author;
	public $enclosure;
	public $guid;
	public $pubDate;
	public $source;
	
	public $category = array();
	
	
	public function __construct($title, $description, $link = '')
	{
		$this->title = $title;
		$this->description = $description;
		$this->link = $link;
	}
	
	public function make_output()
	{
		$output = '
      <item>';
		
		$output .= '
         <title>'. $this->title .'</title>
         <link>'. $this->link .'</link>
         <description>'. $this->description .'</description>';
		
		if(isset($this->guid)) $output .= '
         <guid>'. $this->guid .'</guid>';
		
		if(isset($this->author)) $output .= '
         <author>'. $this->author .'</author>';
		
		if(isset($this->pubDate)) $output .= '
         <pubDate>'. date(DATE_RFC822, $this->pubDate) .'</pubDate>';	
		
		if(is_array($this->source)) $output .= '
         <source url="' . $this->source ['url']. '">'. $this->source ['name'] .'</pubDate>';	
		
		
		if($this->image instanceof RSSImage)
			$output .= $this->image->make_output();
		
		if($this->enclosure instanceof RSSenclosure)
			$output .= $this->enclosure->make_output();
			
			
		foreach($this->categories as $name => $link)
		{
			$link = ($link == 1) ? '' : 'domain="' . $link . '"';
			$output .= '
         <category' . $link . '>' . $name .'</category>';
		}		
		
		$output .= '
      </item>';
		return $output;
	}
	
	public function add_category($name, $link = '')
	{
		$this->categories[$name] = ($link) ? $link : '1';
	}
	
	public function add_source($name, $link = '')
	{
		$this->source['name'] = $name;
		$this->source['url'] = $link;
	}
	
	public function add_enclosure($url, $length, $type = 'audio/mpeg')
	{
		$this->enclosure = new RSSenclosure($url, $length, $type); //($title, $url, $link);
		return $this->enclosure;
	}
	
}

class RSSenclosure
{
	public $url;
	public $length;
	public $type;
	
	public function __construct($url, $length, $type= 'audio/mpeg')
	{
		$this->url = $url;
		$this->length = $length;
		$this->type = $type;
	}
	
	public function make_display()
	{
		
		$output = '
         <enclosure url="'. $this->url .'" length="'. $this->length .'" type="'. $this->type .'" />';
		
		return $output;
	}
}

class RSSImage
{
	
	public $url;
	public $title;
	public $link;
	
	public $width;
	public $height;
	public $description;
	
	public function __construct($title, $url, $link = '')
	{
		$this->title = $title;
		$this->url = $url;
		$this->link = $link;
	}
	
	public function make_output()
	{
		$output = '
         <image>';
		$output .= '
            <title>'. $this->title .'</title>
            <link>'. $this->link .'</link>
            <url>'. $this->url .'</url>';
		
		if(isset($this->width))
			$output .= '
            <width>'. $this->width .'</width>';
		
		if(isset($this->height))
			$output .= '
            <height>'. $this->height .'</height>';
			
		if(isset($this->description))
			$output .= '
            <description>'. $this->description .'</description>';
			
		
		$output .= '
         </image>';

		return $output;
	}
	
}

class RSSCloud
{
	// domain="radio.xmlstoragesystem.com" port="80" path="/RPC2" registerProcedure="xmlStorageSystem.rssPleaseNotify" protocol="xml-rpc"
	
	public $domain;
	public $port;
	public $path;
	public $procedure;
	public $protocol;
	
	public $allowed_protocols = array('xml-rpc', 'soap', 'http-post');
	
	public function __construct($domain, $path, $procedure, $protocol = 'xml-rpc', $port = 80)
	{
		$this->domain = $domain;
		$this->path = $path;
		
		if(in_array($protocol, $this->allowed_protocol))
		$this->procedure = $procedure;
		$this->protocol = $protocol;
		$this->port = $port;
	}
	
	public function make_output()
	{
		$output = '
      <cloud domain="' . $this->domain . '" port="' . $this->port . '" path="' . $this->path . '" registerProcedure="' . $this->procedure . '" protocol="' . $this->protocol . '" />
		';
		return $output;
	}
	
}

?>