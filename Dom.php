<?php
class ABC_Dom extends ABC_Dom_Abstract
{
	protected $_file;
	protected $_document = NULL;
	protected $_storage = array();
	protected $_root;
	protected static $_language = NULL;
	protected static $_domain = 'en';
	
	public function __call($method, $arguments)
	{
		if(!method_exists($this, $method))
		{
			if($method == '_')
				$method = '';
				
			if( !$n = count($arguments) )
			{
				$selector = $method;
			}
			else 
			{
				$selector = '';
				
				foreach($arguments as $argument)
					$selector .= ",$method$argument";
				
				$selector = substr($selector, 1);
			}
			
			return $this->query($selector);
		}
	}
	
	public function __toString()
	{
		return $this->render();
	}
	
	/** Setters **/
	
	public function getFile()
	{
		return $this->_file;
	}
	
	public function setDocument($document)
	{
		$this->_file = $document;
		$this->_document = new ABC_Dom_Document;

		if(file_exists($document))
			$this->_document->load($document);
		else
			throw new Exception("Arquivo <b>$document</b> ao encontrado");	
	}
	
	public function getDocument()
	{
		return $this->_document;
	}
	
	public static function language($language = NULL)
	{
		if($language)
			self::$_language = $language;
		else
			return self::$_language;
	}
	
/**
 * Procura atraves do DOM qualquer query que case com a regra de $selector e cria
 * um novo objeto ABC_Dom_Element que faz referencia a esses objetos
 * @param string, DOMElement, ABC_Dom_Element $selector
 * @param string, DOMElement, ABC_Dom_Element $context
 * @return ABC_Dom_Elementlist
 * @todo implementar $context
 */	
	public function query($selector, $context = NULL)
	{
		try
		{
			if(empty($this->_storage[$selector]))
			{
				$elementlist = new ABC_Dom_Elementlist;
				
				if(strpos($selector, '<') === 0)
				{
					$doc = new DOMDocument;
					@$doc->loadHTML($selector);
					
					$node = new ABC_Dom_Node($doc->getElementsByTagName('body')->item(0)->firstChild);
					$elementlist[]  = $this->_document()->importNode($node, true);
				}
				else
				{
					$expression		= ABC_Dom_Document::cssToXPath($selector);
					$elementlist 	= ABC_Dom_Document::queryXPath($expression, $this->_document, $context);
					//print "CSS: $selector\nXPATH: $expression\n";
				}
							
				$elementlist->setDocument($this->_document);
				
				$this->_storage[$selector] = $elementlist;
				
				return $elementlist;
			}
			else
			{
				return $this->_storage[$selector];
			}
		}
		catch(Exception $e)
		{
			print $e->getMessage();
		}
	}	
	
	public function render($format = NULL)
	{		
//@todo: Implementar xml

		$html = $this->_document->saveHTML($format);
		
		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8,ASCII');

		return $html;
	}
	
	public function save()
	{
		$this->getDocument()->save();
		return $this;
	}
}