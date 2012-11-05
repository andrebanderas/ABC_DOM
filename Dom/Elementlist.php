<?php

/**
 * Armazena um array de elementos ABC_Dom_Node
 * @author Banderas
 */
class ABC_Dom_Elementlist extends ABC_Dom_Abstract implements IteratorAggregate, ArrayAccess
{
    protected $_list = array();
    protected $_length = 0;
    protected $_document = NULL;

	public function __construct($node = NULL)
	{
		if($node instanceof ABC_Dom_Node)
		{
			$this->_list[] = $node;
			$this->_length = 1;
			
			ksort($this->_list);
			
			$this->_document = $node->getDocument();
		}
	}

    public function __call($method, $arguments)
    {
        //Main Methods		
        if (!method_exists($this, $method))
        {
            $maker = '_' . strtolower($method);

            if (method_exists($this, $maker))
            {
                if (!$arguments)
                    return $this->$maker();
                else
                {
                    if (count($arguments) == 1)
                        return $this->$maker($arguments[0]);
                    else
                    {
                        $buffer = array();
                        for ($i = 0; $i < $n = count($arguments);)
                            $buffer[$arguments[$i++]] = $arguments[$i++];

                        return $this->$maker($buffer);
                    }
                }
            }
        }
    }
	
	public function __toString()
	{
		//Somente item atual da lista será retornado
		$child = current($this->_list);
		
		$doc = new DOMDocument();
		$doc->appendChild($doc->importNode($child->_root, true));

        return trim($doc->saveHTML());
	}

    public function setDocument($value)
    {
        $this->_document = $value;
    }

    public function getDocument()
    {
        return $this->_document;
    }

    public function getList()
    {
        return $this->_list;
    }

    public function setList()
    {
        return $this->_list;
    }

    public function getArray()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return array_values($this->_list);
    }

    public function getIterator()
    {
        return new ABC_Dom_Iterator($this->_list);
    }

    public function offsetExists($offset)
    {
        return isset($this->_list[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_list[$offset]) ? $this->_list[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->_list[] = $value;
        $this->_length++;

        ksort($this->_list);
    }

    public function offsetUnset($offset)
    {
        unset($this->_list[$offset]);
        $this->_length--;
    }

    public function reset()
    {
        $this->_list = array_values($this->_list);
        return $this;
    }

    public function reverse()
    {
        krsort($this->_list);
        return $this;
    }

    public function getLength()
    {
        if (!$this->_length)
            $this->_length = count($this->_list);

        return $this->_length;
    }

    public function item($offset)
    {
		if(isset($this->_list[$offset]))
		{
	   		return $this->_list[$offset];
		}
		else
		{
			return NULL;
		}
    }

    public function textToNode($content)
    {
        $doc = new DOMDocument;

        if (strpos($content, '<') !== false)
        {
            try
            {
                if (!@$doc->loadHTML($content))
                    return;
				
				if($doc->getElementsByTagName('body')->length)
					return new ABC_Dom_Node($doc->getElementsByTagName('body')->item(0)->firstChild);
				else if($doc->getElementsByTagName('head')->length)
					return new ABC_Dom_Node($doc->getElementsByTagName('head')->item(0)->firstChild);
				else
					return NULL;
            }
            catch (Exception $e)
            {
                print $e->getMessage();
            }
        }
    }

    public function _create($content)
    {
    	$buffer = new ABC_Dom_Elementlist;
    	$buffer->setDocument($this->_document);
		
        $doc = new DOMDocument;
		@$doc->loadHTML($content);
		
		$node = new ABC_Dom_Node($doc->getElementsByTagName('body')->item(0)->firstChild);
		$buffer[]  = $this->doc->importNode($node, true);
		
		return $buffer;
    }

    public function _each($function, $userdata = null)
    {
        if (is_callable($function))
        {
            array_walk($this->toArray(), $function, $userdata);

            return $this;
        }
    }

    public function _attr($attribute, $value = NULL)
    {
        if ($value)
        {
            foreach ($this as $node)
                $node->setAttribute($attribute, self::shift_tag($node, $value));

            return $this;
        }
        elseif (is_array($attribute))
        {
            foreach ($this as $node)
                foreach ($attribute as $attr => $value)
                    $node->setAttribute($attr, self::shift_tag($node, $value));

            return $this;
        }
        else
        {
            foreach ($this as $node)
            {
                return $node->getAttribute($attribute);
            }
        }
    }
	
	public function _populate($values = array())
	{
		foreach($this as $node)
		{
			if($node->tagName == 'select')
			{
				$reference = $node->getFirstChild();
				
				foreach($values as $key => $item)
				{
					$child = $reference->cloneNode();
					$child->setAttribute('value', $key);
					$child->setHtml($item);
					
					$node->insertBefore($child);
				}
			}
		}
	}
	
	public function _val($value = NULL)
    {
        if ($value)
        {
        	$values = !is_array($value) ? array($value) : $value;
			
            foreach ($this as $node)
			{
				if($node->tagName == 'select')
				{
					$node->removeAttribute('selected');
					
					foreach($values as $value)
					{
						foreach($node->getChildNodes() as $child)
						{
							if($child->hasAttribute('value') && ($child->getAttribute('value') == $value))
							{
								$child->setAttribute('selected', 'selected');
							}
							else
							{
								$html = html_entity_decode($child->getHtml());
								
								if($html == $value || utf8_encode($html) == $value)
									$child->setAttribute('selected', 'selected');
							}
						}
					}
				}
				else if($node->tagName == 'textarea')
				{
					$node->setHtml(self::shift_tag($node, $value));
				}
				else if($node->tagName == 'input' || $node->tagName == 'button')
				{
					if($node->getAttribute('type') == 'checkbox' || $node->getAttribute('type') == 'radio')
					{
						$node->removeAttribute('checked');
						
						foreach($values as $value)
							if($node->getAttribute('value') == $value)
								$node->setAttribute('checked', 'checked');
					}
					else
			    		$node->setAttribute('value', self::shift_tag($node, $value));
				}
			}

            return $this;
        }
        else
        {
        	$buffer = array();
			
            foreach ($this as $node)
            {
            	if($node->tagName == 'select')
				{
					foreach($node->getChildNodes() as $child)
					{
						if($child->getAttribute('selected'))
						{							
							if($child->getAttribute('value') != NULL)
							{
								$buffer[$child->getAttribute('value')] = $child->getHtml();
							}
							else
							{
								$buffer[] = $child->getHtml();
							}
							
							if(!$child->getAttribute('multiple'))
							{								
								if($child->getAttribute('value'))
								{
									return $buffer;
								}
								else
								{
									return array_shift($buffer);
								}
							}
						}
					}
					
					return $buffer;
				}
				else if($node->tagName == 'textarea')
				{
					return $node->getHtml();
				}
				else if($node->tagName == 'input' || $node->tagName == 'button')
				{
            		return $node->getAttribute('value');
				}
            }
        }
    }

    public function _addClass($value)
    {
        if ($value)
        {
            foreach ($this as $node)
            {
                $value = trim($value . ' ' . $node->getAttribute('class'));
                $node->setAttribute('class', $value);
            }
        }

        return $this;
    }
	
	public function _hasClass($value)
	{
		if($value)
		{
			$node = $this->item(0);
			
			return (strpos($node->getAttribute('class'), $value) !== false);
		}
	}

    public function _removeClass($value)
    {
        if ($value)
        {
            foreach ($this as $node)
            {
                $value = str_replace($value, '', $node->getAttribute('class'));
                $node->setAttribute('class', $value);
            }
        }

        return $this;
    }

    public function _removeAttr($attribute)
    {
        if ($attribute)
        {
            foreach ($this as $node)
            {
                $node->removeAttribute($attribute);
            }
        }
    }

    public function _first()
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        foreach ($this as $node)
            $buffer[] = $node->firstChild;

        return $buffer;
    }

    public function _last()
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        foreach ($this as $node)
            $buffer[] = $node->lastChild;

        return $buffer;
    }

    public function _html($content = NULL)
    {
        if ($content !== NULL)
        {
            foreach ($this->list as $node)
            {
                $node->setHTML(self::shift_tag($node, $content), $this->_document);
            }
			
			return $this;
        }
        else
        {
            foreach ($this as $node)
                return $node->html;
        }
    }
  
    public function _prepend($appendlist)
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        if (count($appendlist) > 1)
            $appendlist->reverse();

        if (is_string($appendlist) && strpos($appendlist, '<') !== false)
        {
            $append = $this->textToNode($appendlist);

            foreach ($this as $node)
            {
                $node->insertBefore($append->cloneNode(true), $node->firstChild, $this->_document);
                $buffer[] = new ABC_Dom_Node($node->root);
            }
        }
        else
        {
            foreach ($appendlist as $append)
            {
                foreach ($this as $node)
                {
                    $node->insertBefore($append->cloneNode(true), $node->firstChild, $this->_document);
                    $buffer[] = new ABC_Dom_Node($node->root);
                }

                if ($append->parentNode)
                    $append->parentNode->removeChild($append);
            }
        }

        return $buffer;
    }

    public function _append($appendlist)
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        if (is_string($appendlist) && strpos($appendlist, '<') !== false)
        {
            $append = $this->textToNode(utf8_decode($appendlist));

            foreach ($this as $node)
            {
                $node->insertBefore($append->cloneNode(true), NULL, $buffer->getDocument());
                $buffer[] = new ABC_Dom_Node($node->root);
            }
        }
        else
        {
            foreach ($appendlist as $append)
            {
                foreach ($this as $node)
                {
                	if($node)
					{
	                    $node->insertBefore($append->cloneNode(true), NULL, $this->_document);
	                    $buffer[] = new ABC_Dom_Node($node->root);
					}
                }

                if ($append->parentNode)
                    $append->parentNode->removeChild($append);
            }
        }

        return $buffer;
    }

    public function _after($target)
    {
        //Implement
    }
    
    public function _before($target)
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);
        
        foreach ($target as $ref)
        {
            foreach ($this as $node)
            {
                $node->parentNode->insertBefore($node, $ref, $this->_document);
                $buffer[] = new ABC_Dom_Node($node->root);
            }
        }
    }
    
    public function _contents()
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        foreach ($this as $node)
            foreach ($node->getChildNodes(true) as $child)
                $buffer[] = $child;

        return $buffer;
    }

    public function _empty()
    {
        foreach ($this as $node)
            while ($node->hasChildNodes() && $node->removeChild($node->firstChild));
    }

    public function _remove()
    {
        foreach ($this as $node)
            $node->parentNode->removeChild($node);
    }

    public function _clone()
    {
        $buffer = new ABC_Dom_Elementlist;
        $buffer->setDocument($this->_document);

        foreach ($this as $node)
            $buffer[] = $node->cloneNode(true);

        return $buffer;
    }
	
	public function _is($selector)
	{
		try
		{
			foreach ($this as $node)
        	{
				$expression	= ABC_Dom_Document::cssToXPath($selector);
				$list		= ABC_Dom_Document::queryXPath($expression, $this->_document );
				
				foreach($list as $element)
					if($element == $node)
						return TRUE;
			}
			
			return FALSE;
		}
		catch(Exception $e)
		{
			print $e->getMessage();
		}
	}
	
	public function _find($selector)
	{
		try
		{
			$buffer = new ABC_Dom_Elementlist;
			$buffer->setDocument($this->_document);

			foreach ($this as $node)
        	{
				$expression	= ABC_Dom_Document::cssToXPath($selector);
				$list		= ABC_Dom_Document::queryXPath($expression, $this->_document, $node->root );
				
				if ($list)
	            {
	                foreach ($list as $element)
	                    $buffer[] = $element;
	            }
			}
			
			return $buffer;
		}
		catch(Exception $e)
		{
			print $e->getMessage();
		}
	}	
	
	public function _parent()
	{
		$buffer = new ABC_Dom_Elementlist;
		$buffer->setDocument($this->_document);
		
		foreach ($this as $node)
        {
        	$buffer[] = $node->getParentNode();
		}
		
		return $buffer;
	}
	
	public function _next()
	{
		$buffer = new ABC_Dom_Elementlist;
		$buffer->setDocument($this->_document);
		
		foreach ($this as $node)
        {
        	$buffer[] = $node->getNextSibling();
		}
		
		return $buffer;
	}
	
	public function _prev()
	{
		$buffer = new ABC_Dom_Elementlist;
		$buffer->setDocument($this->_document);
		
		foreach ($this as $node)
        {
        	$buffer[] = $node->getPreviousSibling();
		}
		
		return $buffer;
	}
	
	public function _var($var, $code = NULL)
	{
		$buffer = array();
		
		if(!is_array($var))
			$buffer[$var] = $code;
		else
			$buffer = $var;
			
		foreach($this as $node)
		{
			foreach($buffer as $key => $value)
			{
				$html = str_replace('@'.$key, $value, $node->getHtml());
				$node->setHtml($html, $this->_doc);
			}
		}
	
		return $this;
	}

    private function shift_tag($node, $content)
    {
        $fragments = explode('{{', $content);

        if (count($fragments) > 1)
        {
            foreach ($fragments as $key => $fragment)
                if (false !== $i = strpos($fragment, '}}'))
                    $fragments[$key] = html_entity_decode($node->attributes[substr($fragment, 0, $i)] . substr($fragment, $i + 2));

            return utf8_encode(implode('', $fragments));
        }
        else
            return $content;
    }

}