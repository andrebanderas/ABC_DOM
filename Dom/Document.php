<?php
/*
 * http://css2xpath.appspot.com/
 */
class ABC_Dom_Document extends ABC_Dom_Abstract
{
	const TOKENALL	=	'/[+>~, ]?\s*(\w*[#.][\w\-]+|\w+|\*)*(:[\w\-]+(\([^)]*\))?)*(\[[\w\s]+=?[^\]]*\])*([#.][\w\-]+)*(:[\w\-]+(\([^)]*\))?)*([#.][\w\-]+)*/';
	const TOKENPC	=	'/[+>~, ]?\s*(\w*[#.][\w\-]+|\w+|\*)*(:[\w\-]+(\([^)]*\))?)*|\w+/';
	const TOKENAT	=	'/[+>~, ]?\s*(\w*[#.][\w\-]+|\w+|\*)*(\[[\w\s]+=?[^\]]*\])*([#.][\w\-]+)*/';
	const TOKEN		=	'/[+>~, ]?\s*(\w*[#.][\w\-]+|\w+|\*)*/';
	
	const HTML4		= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n\"http://www.w3.org/TR/html4/strict.dtd\">";
	const HTML4T	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\"http://www.w3.org/TR/html4/loose.dtd\">";
	const HTML4F	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\"http://www.w3.org/TR/html4/frameset.dtd\">";
	const XHTML1	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	const XHTML1T	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
	const XHTML1F	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">";
	const XHTML11	= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">";
	const HTML5		= "<!DOCTYPE html>";
	
	protected $_document;
	protected $_doctype;
	protected $_file;
	
	public function __construct()
	{
		$this->_document = new DOMDocument;
		
		if( !$this->_document->doctype )
		{
			$this->_doctype = 'html5';
		}
	}
	public function getFile()
	{
		return  $this->_file;
	}	
	
	public function getDocumentElement()
	{
		return $this->_document->documentElement;
	}
	
	public function getDoc()
	{
		return $this->_document;
	}
	
	public function setDocument($document)
	{
		$this->_document = $document;
	}
	
	public function getDocument()
	{
		return $this->_document;
	}
	
	public function setFile($file)
	{
		$this->_file = $file;
	}
	
	public function setDoctype($value)
	{
		$this->_doctype = $value;
	}
	
	public function getDoctype()
	{
		return $this->_doctype;
	}
/**
 * @param string $source
 * @return ABC_Dom_Document
 */
	public function load($source)
	{
		try
		{
			if(file_exists($source))
			{
				if(strpos($this->_doctype, 'xhtml'))
					$this->_document->loadXML($source);
				else
				{
					@$this->_document->loadHTMLFile($source);
				}
				
				$this->_file = $source;
			}
			else
			{
				$this->_document->loadHTML($source);
				$this->_file = NULL;
			}
		}
		catch(Exception $e)
		{
			print $e->getMessage();
		}
	}
	
	public function importNode(ABC_Dom_Node $node, $deep = false)
	{
		$n = $this->_document->importNode($node->root, $deep);
		
		return new ABC_Dom_Node($n);
	}
	
	public function saveHTML($format = NULL, $tab = "\t")
	{
		$html = $this->_document->saveHTML();
		
		if($format == 'inline')
		{
			$html = self::clear($html);
		}
		elseif($format == 'tidy')
		{
			if(class_exists('tidy'))
			{
				$tidy = new tidy;
				$html = $tidy->repairString($html, array('indent' => TRUE, 'output-xml' => TRUE , 'wrap' => 500), 'utf8');
			}
			else
			{
				$html = self::tidy($html, $tab);
			}
		}
		
		return $html;
	}
	
	public function createTextNode($text)
	{
		return new ABC_Dom_Node($this->_document->createTextNode($text));
	}
	
	public function createElement($tag, $content = null)
	{
		if($content)
			return new ABC_Dom_Node($this->_document->createElement($tag, $content));
		else
			return new ABC_Dom_Node($this->_document->createElement($tag));
	}
		
	public function save()
	{
		$html = $this->_document->saveHTML();
		
		$this->_document = new DOMDocument;
		$this->_document->loadHTML($html);
	}
	
	public static function queryXPath($expression, $document = NULL, $context = NULL)
	{
		if($document instanceof ABC_Dom_Document)
		{
			$document = $document->getDocument();
		}

		$xp = new DOMXPath( $document );
		
		//print $expression . "<br>";
		
		if($context)
			$nodes = $xp->query(".$expression", $context);
		else
			$nodes = $xp->query($expression);
		
		$elementlist = new ABC_Dom_Elementlist;
		
		foreach($nodes as $node)
			$elementlist[] = new ABC_Dom_Node($node, $document);
		
		return $elementlist;
	}
	
	static public function cssToXPath($selector)
	{
		$matches = self::makeTokens($selector);
		
		return self::makeXPath($matches);
	}
	
	static public function makeXPath($matches)
	{
		$expr = '';
		
		foreach($matches as $key => $match)
		{			
			if(strpos($match, '+') === 0)
			{
				$selector = self::predicate(trim(substr($match, 1)), false);
				
				$expr .= '/following-sibling::*[1]/self::' . $selector;
			}
			elseif(strpos($match, '~') === 0)
			{
				$selector =  self::predicate(trim(substr($match, 1)), false);
				
				$expr .= '/following-sibling::' . $selector;
			}
			elseif(strpos($match, ',') === 0)
			{
				$selector = self::predicate(trim(substr($match, 1)));
				
				$expr .= '|' . $selector;
			}
			elseif(strpos($match, '>') === 0)
			{
				$selector =  self::predicate(trim(substr($match, 1)), false);
				
				$expr .= '/' . $selector;
			}
			else
			{
				$selector = self::predicate(trim($match));
				
				$expr .= $selector;
			}
		}
		
		return $expr;
	}
	
	static public function makeTokens($selector)
	{
		if(strpos($selector, '[') !== false && strpos($selector, ':') !== false)
			preg_match_all( self::TOKENALL , $selector, $tokens);
		elseif(strpos($selector, '[') !== false)
			preg_match_all( self::TOKENALL , $selector, $tokens);
		elseif(strpos($selector, ':') !== false)
			preg_match_all( self::TOKENPC , $selector, $tokens);
		else
			preg_match_all( self::TOKEN , $selector, $tokens);
		
		$matches = array();
		
		foreach($tokens[0] as $token)
		{
			$token = trim($token);
			
			if($token)
				$matches[] = $token;	
		}
		
		return $matches;
	}
	
	static public function predicate($selector, $root = true)
	{
		preg_match_all('/^[\w]+/', $selector, $match);
		
		$tag = isset($match[0][0]) ? $match[0][0] : '*';
		
		preg_match_all('/^\w+|#[\w\-]+|(\.+[\w\-]+)+|\[[\w\s]+=?[^]]*\]|:\w+\-*\w*\(?[^\)]*\)?/', $selector, $matches);
		
		$selector = $tag;
		
		foreach($matches[0] as $key => $match)
		{			
			if(strpos($match, '#') === 0)
			{
				$match = substr($match,1);
				
				if($root)
					$selector = "//$tag";
				
				$selector .= "[@id='$match']";
			}
			elseif(strpos($match, '.') === 0)
			{
				$match = substr(str_replace('.', ' ', trim($match)),1);
				
				if($root)
					$selector = "//$tag";
				
				$selector .= "[contains(concat(' ', normalize-space(@class), ' '), ' $match ')]";
			}
			elseif(strpos($match, '[') === 0)
			{
				$match = trim(substr($match, 1, -1)); //remove [] of [attr=value]
				
				if(strpos($match, '=') === false) //[value]
				{
					$selector = "//$tag"."[@$match]";
				}
				else
				{
					list($attr, $value) = explode('=', $match, 2);
					
					$attr = trim($attr);
					$value = trim($value);

					if(strpos($value, '"') === 0 || strpos($value, '\'') === 0)
					{
						$value = substr($value,1,-1); // Remove " and '
					}
					
					if(strpos($attr, '^') !== false)
					{
						$attr = trim(substr($attr, 0, -1));
						
						$selector = "//$tag"."[starts-with(@$attr, '$value')]";
					}
					elseif(strpos($attr, '!') !== false)
					{						
						$attr = trim(substr($attr, 0, -1));
						
						$selector = "//$tag"."[not(@$attr) or @$attr != '$value']";
					}
					elseif(strpos($attr, '*') !== false)
					{						
						$attr = trim(substr($attr, 0, -1));
						
						$selector = "//$tag"."[contains(@$attr, '$value')]";
					}
					elseif(strpos($attr, '|') !== false)
					{
						$attr = trim(substr($attr, 0, -1));
						
						$selector = "//$tag"."[@$attr = '$value' or starts-with(@$attr, '$value-')]";
					}
					elseif(strpos($attr, '~') !== false)
					{
						$attr = trim(substr($attr, 0, -1));
						
						$selector = "//$tag"."[contains(concat(' ', normalize-space(@$attr), ' '), ' $value ')]";
					}
					elseif(strpos($attr, '$') !== false)
					{							
						$attr = trim(substr($attr, 0, -1));
						$len = strlen($value) -1;
						
						$selector = "//$tag"."[substring(@$attr, string-length(@$attr)-$len) = '$value']";
					}
					else
					{
						$attr = trim($attr);
						
						$selector = "//$tag"."[@$attr = '$value']";
					}
				}
			}
			elseif(strpos($match, ':') === 0)
			{				
				if(strpos($match, ':button') === 0)
				{
					if($tag == 'input' || $tag == '*' || $tag == 'button' )
					{
						$selector = "input[@type='button'] | //button";
					}
				}
				elseif(strpos($match, ':checkbox') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='checkbox']";
					}
				}
				elseif(strpos($match, ':checked') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "//input[@checked]";
					}
				}
				elseif(strpos($match, ':contains') === 0)
				{
					$v = trim(substr($match, strpos($match, '(') + 1, -1));
					
					if(strpos($v, '"') === 0 || strpos($v, '\'') === 0)
						$v = substr($v,1,-1);
						
					$selector = "//$tag"."[contains(., '$v')]";
				}
				elseif(strpos($match, ':disabled') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@disabled]";
					}
				}
				elseif(strpos($match, ':empty') === 0)
				{
					$selector = "//$tag"."[not(node())]";
				}
				elseif(strpos($match, ':enabled') === 0) //Comportamento estranho com legends em jquery
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[not(@disabled)]";
					}
				}
				elseif(strpos($match, ':eq') === 0)
				{
					$v = trim(substr($match, strpos($match, '(') + 1, -1)) + 1;
						
					$selector = "(//$tag)[$v]";
				}
				elseif(strpos($match, ':even') === 0)
				{
					$selector = "//$tag"."[position() mod 2 = 1 and position() >= 0]";
				}
				elseif(strpos($match, ':file') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='file']";
					}
				}
				elseif(strpos($match, ':first') === 0)
				{
					$selector = "(//$tag)[1]";
				}
				elseif(strpos($match, ':first-child') === 0)
				{
					$selector = "//$tag"."[1]";
				}
				elseif(strpos($match, ':gt') === 0)
				{
					$v = trim(substr($match, strpos($match, '(') + 1, -1)) + 2;
					
					$selector = "(//$tag)[position() >= $v]";
				}
// @todo: Implementar HAS
//				elseif(strpos($match, ':has') === 0)
//				{
//					$v = trim(substr($match, strpos($match, '(') + 1, -1));
//					
//					$selector = self::cssToXPath($v);
//					
//					$selector = "//*[. = ($selector)]"; //name() = name($selector) :: bug para elementos vazios
//				}
				elseif(strpos($match, ':header') === 0)
				{
					$selector = "h1 | //h2 | //h3 | //h4 | //h5 | //h6";
				}
				elseif(strpos($match, ':hidden') === 0) //Apenas em inputs e naum em todos os elementos
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='hidden']";
					}
				}
				elseif(strpos($match, ':image') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='image']";
					}
				}
				elseif(strpos($match, ':input') === 0)
				{
					$selector = "input | //textarea | //select | //button";
				}
				elseif(strpos($match, ':last') === 0)
				{
					$selector = "(//$tag)[last()]";
				}
				elseif(strpos($match, ':last-child') === 0)
				{
					$selector = "//$tag"."[last()]";
				}
				elseif(strpos($match, ':lt') === 0)
				{
					$v = trim(substr($match, strpos($match, '(') + 1, -1));
					
					$selector = "(//$tag)[position() <= $v]";
				}
				
// @todo NOT: Parcialemnte implementado, testado apenas com o seletor [atributo]

				elseif(strpos($match, ':not') === 0)
				{
					$v = trim(substr($match, strpos($match, '(') + 1, -1));
					
					$selector = self::cssToXPath($v);
					
					if(strpos($selector, ']'))
						$selector = substr(str_replace('//*','',$selector), 1, -1);
					
					$selector = "//$tag"."[not($selector)]";
				}
				
// @FIXME: Funcionamento estranho com 'n' negativo
				elseif(strpos($match, ':nth-child') === 0)
				{
					$b = trim(substr($match, strpos($match, '(') + 1, -1));

					$a = 0;
					
					if(is_numeric($b))
					{
						$selector = "//$tag"."[$b]";
					}
					else
					{
						if($b == 'odd')
						{
							$a = 2;
							$b = 1;
						}
						elseif($b == 'even')
						{
							$a = 2;
							$b = 0;
						}
						elseif(strpos($b, 'n') === 0)
						{
							list($a, $b) = explode('n', $b);
				
							$a = ($a == '-')? -1 : ((!$a || $a == '+') ? 1 : $a);						
							$b = (!isset($b) || $b == '') ? 0 : (int) $b;
						}
						
						$c = ($b > 0) ? -$b : "+".-$b;
						$d = ($b > 0) ? "and position() >= $b" : "";

						if($tag == '*')
							$selector = "*/*[(position() $c) mod $a = 0 $d]";
						else
							$selector = "*/*[name() = '$tag' and ((position() $c) mod $a = 0 $d)]";
					}
				}
				elseif(strpos($match, ':odd') === 0)
				{
					$selector = "//$tag"."[position() mod 2 = 0 and position() >= 0]";
				}
				elseif(strpos($match, ':only-child') === 0)
				{
					$selector = "//$tag"."/*[count(child::node()) = 1]";
				}
				elseif(strpos($match, ':parent') === 0)
				{
					$selector = "//$tag"."[parent::node()]";
				}
				elseif(strpos($match, ':password') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='password']";
					}
				}
				elseif(strpos($match, ':radio') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='radio']";
					}
				}
				elseif(strpos($match, ':selected') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "option[@selected]";
					}
				}
				elseif(strpos($match, ':text') === 0)
				{
					if($tag == 'input' || $tag == '*')
					{
						$selector = "input[@type='text']";
					}
				}
				elseif(strpos($match, ':visible') === 0) //Somente em forms
				{
					$selector = "//$tag"."[not(@type='hidden')]";
				}
			}
			else
			{
				if($root)
					$selector = "//$tag";
				else
					$selector = $tag;
			}
		}
		
		return $selector;
	}

	public static function clear($html)
	{
		$html = str_replace(array("\n", "\t", "\r", "&#13;"), '', $html);
		
		/* Remove comment lines in CSS and Style */
		
		if(strpos($html, '/*') !== false)	
			$html = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/' , '' , $html );
		
		return $html;
	}
	
	private function tidy($html, $tab)
	{
		$html = self::clear($html);
		
		if($html)
		{
			$inline = array('a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'li', 'dt', 'dd', 'abbr', 'acronym', 'dfn', 'em', 'strong', 'code', 'samp', 'kbd', 'var', 'span', 'b', 'i', 'bdo', 'cite', 'del', 'ins', 'q', 'button', 'script', 'sup', 'sub', 'object', 'map', 'legend', 'textarea', 'option', 'td', 'th', 'caption', 'col', 'colgroup', 'iframe', 'title', 'style', 'script');
			
			$doc = new DOMDocument;
			$doc->loadHTML($html);
			
			$html = self::format($doc->documentElement, $tab);
			
			foreach($inline as $tag)
				$html = preg_replace("/<".$tag."([^>]*)>(.*)\n$tab*<\/$tag>/","<$tag$1>$2</$tag>", $html);
			
			switch ($this->_doctype)
			{
				case 'html4'	:
					$html = self::HTML4 . $html; 
					break;
				case 'html4t'	:
					$html = self::HTML4T . $html; break;
					break;
				case 'html4f'	: 
					$html = self::HTML4F . $html; break;
					break;
				case 'xhtml1'	: 
					$html = self::XHTML1 . $html; break;
					break;
				case 'xhtml1t'	: 
					$html = self::XHTML1T . $html; break;
					break;
				case 'xhtml1f'	: 
					$html = self::XHTML1F . $html; break;
					break;
				case 'xhtml11'	: 
					$html = self::XHTML11 . $html; break;	
					break;
				default :
					$html = self::HTML5 . $html; break;
			}
				
			return $html;
		}
	}
	
	private function format($element, $tab, $tag = '', $k = -1)
	{	
		$empty = array('area', 'br', 'hr', 'input', 'img', 'meta', 'link', 'param', 'audio', 'video', 'base');
		$ind = '';
		
		if($element->nodeType == 3)
		{
			$html = $element->textContent;
		}
		else
		{
			for($i=0; $i<$k; $i++)
				$ind .= $tab;
				
			if($element->nodeType == 1)
			{
				$tag = $element->tagName;
	
				$attr = '';
	
				foreach($element->attributes as $a => $v)
					$attr .= " $a=\"".$v->value .'"';
	
				$html = "\n$ind<$tag$attr>";
	
				foreach($element->childNodes as $node)
				{				
					$html .= self::format($node, $tab, $tag, ++$k);
					$k--;
				}
	
				if(!in_array($tag,$empty))
					$html .= "\n$ind</$tag>";
				else
					$html = substr($html, 0, -1) . '/>';
			}
			elseif($element->nodeType == 4)
			{
				$ind = substr($ind, strlen($tab));
				
				if($tag == 'style')
					$html = self::formatEcma($element->textContent, $ind);
				elseif($tag == 'script')
					$html = self::formatEcma($element->textContent, $ind);
			}
			else
			{
				$html = "\n$ind<!--" . $element->data . "-->";
			}
		}
		
		return $html;
	}
	
	private function formatEcma($text, $tab)
	{
		$buffer = array();

		$text = str_replace(
			array('{ ', ' }', ' ;'),
			array('{', '}', ';'), 
		$text);
		
		$text = str_replace(
			array('{', '}', ';'),
			array("\n{\n", "\n}\n", ";\n"), 
		$text);
		
		$text = str_replace(
			array("}\n)", "\n\n"),
			array('})', "\n"), 
		$text);
		
		$buffer = explode("\n", $text);
		
		$ind = $tab.$tab;
		$text = "\n\n";
		
		foreach($buffer as $item)
		{
			if($item == '}' || $item == '});')
			{
				$ind = substr($ind, strlen($tab));
			}
			
			if(trim($item))
				$text .= $ind.$item."\n";
			
			if($item == '{')
			{
				$ind .= $tab;
			}
		}
		
		return $text;
	}
}