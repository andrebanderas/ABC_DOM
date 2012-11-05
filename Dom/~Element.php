<?php

class ABC_Dom_Element extends ABC_Abstract
{

    private $_root;
    private $_selector = '';
    private $_results = array();

    public function __construct($node)
    {
        $this->_root = $node;
    }

    public function getResults()
    {
        if (!$this->_results)
        {
            $this->_results = new ABC_Dom_Elementlist;
            $this->_results[] = new ABC_Dom_Node($this->_root);
        }

        return $this->_results;
    }

    public function getRoot()
    {
        return $this->_root;
    }

    /**
     * @param string $tagName
     * @return ABC_Dom_Nodelist
     */
    public function getElementsByTagName($tagName)
    {
        $elementList = new ABC_Dom_Elementlist;

        foreach ($this->_document->getElementsByTagName($tagName) as $element)
        {
            $elementList[] = new ABC_Dom_Node($element);
        }

        return $elementList;
    }

    /**
     * Cria um objeto que representa os Nodulos DOM do Documento de acordo com a busca feita atraves de um seletor CSS3
     * @param string $selector: Seletor CSS3 
     * @return ABC_Dom_Elementlist
     * @todo Fazer o processo ficar mais rapido
     */
    public function querySelectorAll($selector)
    {
        $matches = ABC_Dom::makeTokens($selector);

        $this->_selector = $selector;

        $root = $this->_results;

        $buffer = array();

        $k = 0;

        foreach ($matches as $match)
        {
            if (strpos($match, ',') !== false)
            {
                $this->_results = $root;
                $match = substr($match, 1);
                $k++;
            }

            $buffer[$k] = self::querySelector(trim($match))->results;
        }

        $list = new ABC_Dom_Elementlist;

        foreach ($buffer as $nodelist)
            foreach ($nodelist as $node)
                $list[] = $node;

        if ($list->length)
            return $list;
        else
            return NULL;
    }

    private function querySelector($selector)
    {
        if ($this->results)
        {
            $nodes = new ABC_Dom_Elementlist;

            foreach ($this->results as $item)
            {
                if (strpos($selector, '+') === 0)
                {
                    $nodelist = $this->findNextAdjacents($item, trim(substr($selector, 1)));
                }
                elseif (strpos($selector, '~') === 0)
                {
                    $nodelist = $this->findNextSiblings($item, trim(substr($selector, 1)));
                }
                elseif (strpos($selector, '>') === 0)
                {
                    $nodelist = $this->findChilds($item, trim(substr($selector, 1)));
                }
                else
                {
                    $nodelist = $this->findElements($item, $selector);
                }

                foreach ($nodelist as $item)
                    if (!self::isin($item, $nodes))
                        $nodes[] = $item;

                $this->_results = $nodes;
            }

            return $this;
        }
        else
        {
            throw new Exception("Selector don't found\n");
        }
    }

    private function getTag($selector)
    {
        preg_match_all('/^[\w]+/', $selector, $match);

        return isset($match[0][0]) ? $match[0][0] : '*';
    }

    private function findElements($element, $selector)
    {
        $buffer = new ABC_Dom_Elementlist;

        $nodelist = $element->getElementsByTagName(self::getTag($selector));

        foreach ($nodelist as $node)
        {
            if (self::has($node, $selector))// && !self::in($node, $buffer))
            {
                $buffer[] = $node;
            }
        }

//		if(strpos($selector, ':') !== false)
//			$buffer = self::filter($buffer, $selector);

        return $buffer;
    }

    private function findChilds($element, $selector)
    {
        $buffer = new ABC_Dom_Elementlist;

        $nodelist = $element->getChildNodes();

        foreach ($nodelist as $node)
        {
            if (self::has($node, $selector))// && !self::in($node, $buffer))
            {
                $buffer[] = $node;
            }
        }

//		if(strpos($selector, ':') !== false)
//			$buffer = self::filter($buffer, $selector);

        return $buffer;
    }

    private function findNextSiblings($element, $selector)
    {
        $buffer = new ABC_Dom_Elementlist;

        $nodelist = $element->getAllNextSiblings();

        foreach ($nodelist as $node)
        {
            if (self::has($node, $selector))
            {
                $buffer[] = $node;
            }
        }

        return $buffer;
    }

    private function findNextAdjacents($element, $selector)
    {
        $buffer = new ABC_Dom_Elementlist;

        $node = $element->getNextSibling();

        if ($node && self::has($node, $selector))// && !self::in($node, $buffer))
        {
            $buffer[] = $node;
        }

        return $buffer;
    }

    private function isin($node, $list)
    {
        foreach ($list as $item)
            if ($node == $item)
                return true;

        return false;
    }

    private function has(ABC_Dom_Node $node, $selector)
    {
        preg_match_all('/^\w+|#[\w\-]+|(\.+[\w\-]+)+|\[[\w\s]+=?[^]]*\]|:\w+\-*\w*\(?[^\)]*\)?/', $selector, $matches);

        $check = true;

        foreach ($matches[0] as $match)
        {
            if (strpos($match, '#') === 0)
            {
                $check &= ($node->getAttribute('id') == substr($match, 1));
            }
            elseif (strpos($match, '.') === 0)
            {
                $match = explode('.', substr($match, 1));
                $class = explode(' ', $node->getAttribute('class'));

                $check &= (count(array_diff($match, $class)) == 0);
            }
            elseif (strpos($match, ':') === 0)
            {
                if (strpos($match, ':button') !== false)
                {
                    $check &= ($node->tag == 'button' || ($node->tag == 'input' && $node->getAttribute('type') == 'button'));
                }
                elseif (strpos($match, ':checkbox') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'checkbox');
                }
                elseif (strpos($match, ':checked') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->hasAttribute('checked'));
                }
                elseif (strpos($match, ':contains') !== false)
                {
                    $v = trim(substr($match, strpos($match, '(') + 1, -1));
                    if (strpos($v, '"') === 0 || strpos($v, '\'') === 0)
                        $v = substr($v, 1, -1);

                    $check &= (strpos($node->textContent, $v) !== false);
                }
                elseif (strpos($match, ':disabled') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->hasAttribute('disabled'));
                }
                elseif (strpos($match, ':empty') !== false)
                {
                    $check &= (!$node->hasChildNodes());
                }
                elseif (strpos($match, ':enabled') !== false)
                {
                    $check &= ($node->tag == 'input' && !$node->hasAttribute('disabled'));
                }
                elseif (strpos($match, ':file') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'file');
                }
                elseif (strpos($match, ':first-child') !== false)
                {
                    $e = $node->parentNode->firstChild;

                    $check &= ($e === $node);
                }
                elseif (strpos($match, ':last-child') !== false)
                {
                    $e = $node->parentNode->lastChild;

                    $check &= ($e === $node);
                }
                elseif (strpos($match, ':ntn-child') !== false)
                {
                    $b = trim(substr($match, strpos($match, '(') + 1, -1));

                    $a = 0;

                    if ($b == 'odd')
                    {
                        $a = 2;
                        $b = 1;
                    }
                    elseif ($b == 'even')
                    {
                        $a = 2;
                        $b = 0;
                    }
                    elseif (strpos($b, 'n') !== false)
                    {
                        list($a, $b) = explode('n', $b);

                        $a = ($a == '-') ? -1 : ((!$a || $a == '+') ? 1 : $a);
                        $b = (!isset($b) || $b == '') ? 0 : $b;
                    }

                    $i = 1;
                    $k = false;
                    $j = 0;

                    if (0 < $a || 0 < $b)
                    {
                        foreach ($node->parentNode->childNodes as $e)
                        {
                            while (0 >= $r = ($a * $j + $b))
                                $j++;

                            if ($i == $r)
                                if ($e === $node)
                                {
                                    $k = true;
                                    break;
                                }
                                else
                                    $j++;

                            $i++;
                        }
                    }

                    $check &= $k;
                }
                elseif (strpos($match, ':has') !== false)
                {
                    $v = trim(substr($match, strpos($match, '(') + 1, -1));
                    $k = false;

                    foreach ($node->getElementsByTagName('*') as $e)
                    {
                        if (self::has($e, $v))
                        {
                            $k = true;
                            break;
                        }
                    }

                    $check &= $k;
                }
                elseif (strpos($match, ':header') !== false)
                {
                    $check &= ( in_array($node->tag, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6')) );
                }
                elseif (strpos($match, ':hidden') !== false)
                {
                    /** Funcionamento diferente da jquery pois soh funciona com campos do tipo hidden * */
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'hidden');
                }
                elseif (strpos($match, ':image') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'image');
                }
                elseif (strpos($match, ':input') !== false)
                {
                    $check &= ( in_array($node->tag, array('input', 'textarea', 'select', 'button')) );
                }
                elseif (strpos($match, ':not') !== false)
                {
                    /** Funcionamento diferente da jquery pois naum aceita childs nem siblings * */
                    $v = trim(substr($match, strpos($match, '(') + 1, -1));
					
                    $k = false;

                    foreach ($node->getElementsByTagName('*') as $e)
                    {
                        if (!self::has($e, $v))
                        {
                            $k = true;
                            break;
                        }
                    }

                    $check &= $k;
                }
                elseif (strpos($match, ':password') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'password');
                }
                elseif (strpos($match, ':parent') !== false)
                {
                    $check &= ($node->hasChildNodes());
                }
                elseif (strpos($match, ':radio') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'radio');
                }
                elseif (strpos($match, ':reset') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'reset');
                }
                elseif (strpos($match, ':selected') !== false)
                {
                    $check &= ($node->tag == 'option' && $node->hasAttribute('selected'));
                }
                elseif (strpos($match, ':submit') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'submit');
                }
                elseif (strpos($match, ':text') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') == 'text');
                }
                elseif (strpos($match, ':visible') !== false)
                {
                    $check &= ($node->tag == 'input' && $node->getAttribute('type') != 'hidden');
                }
            }
            elseif (strpos($match, '[') === 0)
            {
                $match = trim(substr($match, 1, -1)); //remove [] of [attr=value]

                if (strpos($match, '=') === false) //[value]
                {
                    $check &= ($node->hasAttribute($match));
                }
                else
                {
                    list($attr, $value) = explode('=', $match, 2);

                    $attr = trim($attr);
                    $value = trim($value);

                    if (strpos($value, '"') === 0 || strpos($value, '\'') === 0)
                    {
                        $value = substr($value, 1, -1); // Remove " and '
                    }

                    $v = $node->getAttribute(trim(substr($attr, 0, -1)));

                    if (strpos($attr, '^') !== false)
                    {
                        $check &= (strpos($v, $value) === 0);
                    }
                    elseif (strpos($attr, '!') !== false)
                    {
                        $check &= (strpos($v, $value) === false);
                    }
                    elseif (strpos($attr, '*') !== false)
                    {
                        $check &= (strpos($v, $value) !== false);
                    }
                    elseif (strpos($attr, '|') !== false)
                    {
                        $check &= ($value == (($n = strpos($v, '-')) ? substr($v, 0, $n) : $v));
                    }
                    elseif (strpos($attr, '~') !== false)
                    {
                        $check &= (strpos(' ' . $v, $value) !== false);
                    }
                    elseif (strpos($attr, '$') !== false)
                    {
                        $check &= ((strrpos($v, $value) - strlen($v) + strlen($value)) === 0);
                    }
                    else
                    {
                        $check &= ($node->getAttribute($attr) == $value);
                    }
                }
            }
            else
            {
                $match = ($match == '') ? '*' : $match;
                $check &= ($match == $node->tag || $match == '*');
            }
        }

        return $check;
    }

}