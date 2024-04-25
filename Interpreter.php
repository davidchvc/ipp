<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use DOMElement;




class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {

        // TODO: Start your code here
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:
        //$dom = $this->source->getDOMDocument();
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        //throw new NotImplementedException;

        $dom = $this->source->getDOMDocument();


        $variables = [];
        $labels = [];
        $instructions = [];
        $usedOpcodes = [];
        $maxinstruction = 0;
        $instructioncounter = 0;
        $callStack = [];
        //cyklus prochazející celý domdokument s vstupním kodem ukládá do pole instrukce a zároven dochází k kontrole order hodnot instrukcí
        foreach ($dom->documentElement->childNodes as $node) {

            if ($node instanceof DOMElement) {

                $tagName = $node->tagName;

                if ($tagName === 'instruction') {
                    $orderValue = intval($node->getAttribute('order'));
                    if ($orderValue > $maxinstruction) {
                        $maxinstruction = $orderValue;
                    }

                    if ($orderValue < 1 || !is_numeric($orderValue)) {
                        exit(32);
                    }


                    if (in_array($orderValue, $usedOpcodes)) {
                        exit(32);
                    }

                    $usedOpcodes[] = $orderValue;
                    $instructions[$orderValue] = $node;
                } else {
                    exit(32);
                }
            }
        }
        //seřadení instrukcí podle order hodnoty
        ksort($instructions);

        //cyklus pro uložení jmen label instrukcí a jejich pozici v kódu
        foreach ($instructions as $order => $instruction) {
            $tagName = $instruction->getAttribute('opcode');
            if ($tagName === 'LABEL') {

                $labelName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

                if (isset($labels[$labelName])) {
                    exit(52);
                }

                $labels[$labelName] = $order;
            }
        }
        //cyklys prochazející veškeré uložené instrukce a podle typu instrukce dochází k volání jednotlivých objektů které vykonávají příslušne instrukce dle typu instrukce z vstupního kódu
        while ($instructioncounter <= $maxinstruction) {

            if (!isset($instructions[$instructioncounter])) {
                $instructioncounter++;
                continue;
            }

            $instruction = $instructions[$instructioncounter];


            $instructionName = $instruction->getAttribute('opcode');

            switch ($instructionName) {
                case 'MOVE':

                    $moveInstruction = new MoveInstruction();
                    $moveInstruction->execute($instruction, $variables);

                    break;
                case 'CREATEFRAME':


                    break;
                case 'PUSHFRAME':


                    break;
                case 'POPFRAME':


                    break;
                case 'DEFVAR':
                    $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;
                    if ($varName === null) {
                        exit(32);
                    }
                    $variables[$varName] = null;

                    break;
                case 'CALL':

                    $labelName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

                    if (!isset($labels[$labelName])) {
                        exit(52);
                    }

                    array_push($callStack, $instructioncounter);

                    $instructioncounter = $labels[$labelName];
                    break;
                case 'RETURN':
                    if (empty($callStack)) {
                        exit(56);
                    }

                    $instructioncounter = array_pop($callStack);

                    break;
                case 'PUSHS':


                    break;
                case 'POPS':



                    break;
                case 'ADD':

                    $addInstruction = new AddInstruction();
                    $addInstruction->execute($instruction, $variables);


                    break;
                case 'SUB':

                    $subInstruction = new SubInstruction();
                    $subInstruction->execute($instruction, $variables);
                    break;
                case 'MUL':

                    $mulInstruction = new MulInstruction();
                    $mulInstruction->execute($instruction, $variables);
                    break;
                case 'IDIV':

                    $idivInstruction = new IdivInstruction();
                    $idivInstruction->execute($instruction, $variables);
                    break;
                case 'LT':

                    $ltInstruction = new LtInstruction();
                    $ltInstruction->execute($instruction, $variables);
                    break;
                case 'GT':

                    $gtInstruction = new GtInstruction();
                    $gtInstruction->execute($instruction, $variables);
                    break;
                case 'EQ':

                    $eqInstruction = new EqInstruction();
                    $eqInstruction->execute($instruction, $variables);
                    break;
                case 'AND':
                    $andInstruction = new AndInstruction();
                    $andInstruction->execute($instruction, $variables);
                    break;
                case 'OR':
                    $orInstruction = new OrInstruction();
                    $orInstruction->execute($instruction, $variables);
                    break;
                case 'NOT':
                    $notInstruction = new NotInstruction();
                    $notInstruction->execute($instruction, $variables);
                    break;
                case 'INT2CHAR':
                    $int2charInstruction = new Int2CharInstruction();
                    $int2charInstruction->execute($instruction, $variables);
                    break;
                case 'STRI2INT':
                    $stri2intInstruction = new Stri2IntInstruction();
                    $stri2intInstruction->execute($instruction, $variables);
                    break;
                case 'READ':
                    //implementace instrukce read
                    $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;
                    if ($varName === null) {
                        exit(32);
                    }

                    $typeNode = $instruction->getElementsByTagName('arg2')->item(0);
                    if ($typeNode === null) {
                        exit(32);
                    }
                    $type = $typeNode->nodeValue;

                    switch ($type) {
                        case 'int':
                            $input_value = $this->input->readInt();
                            break;
                        case 'string':
                            $input_value = $this->input->readString();
                            break;
                        case 'bool':

                            $input_value = $this->input->readBool();
                            break;
                        default:
                            exit(32);
                    }
                    $variables[$varName] = $input_value;
                    break;
                case 'WRITE':
                    //volani funkce pro vykonani isntrukce write a následá kontrola výstupní hodnoty a rozhodnutí o jaký typ výstupu se jedná
                    $writeInstruction = new WriteInstruction();

                    $writeInstructionresult = $writeInstruction->execute($instruction, $variables);
                    if ($writeInstructionresult !== null && $writeInstructionresult !== 'nil') {
                        $text = $writeInstructionresult;



                        if (is_numeric($text)) {
                            $this->stdout->writeInt($text);
                        } elseif (is_bool($text)) {
                            $this->stdout->writeBool($text);
                        } else {


                            $this->stdout->writeString($text);
                        }
                    }

                    break;
                case 'CONCAT':
                    $concatInstruction = new ConcatInstruction();
                    $concatInstruction->execute($instruction, $variables);
                    break;
                case 'STRLEN':
                    $strlenInstruction = new StrlenInstruction();
                    $strlenInstruction->execute($instruction, $variables);
                    break;
                case 'GETCHAR':
                    $getcharInstruction = new GetCharInstruction();
                    $getcharInstruction->execute($instruction, $variables);
                    break;
                case 'SETCHAR':
                    $setcharInstruction = new SetCharInstruction();
                    $setcharInstruction->execute($instruction, $variables);
                    break;
                case 'TYPE':
                    $typeInstruction = new TypeInstruction();
                    $typeInstruction->execute($instruction, $variables);
                    break;
                case 'LABEL':

                    break;
                case 'JUMP':
                    //vykonání instrukce jump kde dochází k kontrole existence label a následné skončení na pořadí instrukce label
                    $labelName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;
                    if (isset($labels[$labelName])) {

                        $jumpToOrder = $labels[$labelName];

                        $instructioncounter = $jumpToOrder;
                    } else {
                        exit(52);
                    }
                    break;
                case 'JUMPIFEQ':
                    //kontrola exitence label pro skok a následné skočení na danou instrukci
                    $jumpeq = new JumpIfEqInstruction();

                    $jumpeqresult = $jumpeq->execute($instruction, $variables);

                    if ($jumpeqresult) {
                        $labelName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


                        if (isset($labels[$labelName])) {


                            $jumpToOrder = $labels[$labelName];

                            $instructioncounter = $jumpToOrder;
                        } else {
                            exit(52);
                        }
                    }

                    break;
                case 'JUMPIFNEQ':
                    $jumpeq = new JumpIfNEqInstruction();

                    $jumpeqresult = $jumpeq->execute($instruction, $variables);

                    if ($jumpeqresult) {
                        $labelName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


                        if (isset($labels[$labelName])) {


                            $jumpToOrder = $labels[$labelName];

                            $instructioncounter = $jumpToOrder;
                        } else {
                            exit(52);
                        }
                    }
                    break;
                case 'EXIT':
                    $exitInstruction = new ExitInstruction();
                    $exitInstruction->execute($instruction, $variables);

                    break;
                case 'DPRINT':

                    $dprintInstruction = new DPrintInstruction();

                    $dprintInstructionresult = $dprintInstruction->execute($instruction, $variables);
                    $this->stderr->writeString($dprintInstructionresult);

                    break;
                case 'BREAK':


                    break;
                default:
                    exit(32);
            }
            $instructioncounter++;
        }


        return (0);
    }
}



class MoveInstruction
{
    /**
     * Provádí instrukci typu MOVE, která kopíruje hodnotu z jedné proměnné do druhé.
     * Získává cílovou proměnnou a hodnotu zadanou buď přímo nebo z jiné proměnné podle argumentů instrukce.
     * Ukládá získanou hodnotu do cílové proměnné v proměnných.
     *
     * @param DOMElement $instruction XML element reprezentující instrukci MOVE.
     * @param array<string, mixed> $variables Asociativní pole uchovávající proměnné, kde klíče jsou názvy proměnných a hodnoty jsou jejich aktuální hodnoty.
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {

        $targetVarName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;
        if ($targetVarName === null) {
            exit(32);
        }


        $sourceArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        if ($sourceArgNode === null) {
            exit(32);
        }
        $typeAttributeValue = $sourceArgNode->getAttribute('type');

        $sourceValue = null;
        if ($typeAttributeValue === 'var') {

            $sourceVarName = $sourceArgNode->nodeValue;

            $sourceValue = $variables[$sourceVarName];
        } else {

            $sourceValue = $sourceArgNode->nodeValue;
        }


        $variables[$targetVarName] = $sourceValue;
    }
}

class AddInstruction
{
    /**
     * Provádí instrukci typu ADD, která provádí sčítání dvou symbolů a výsledek ukládá do proměnné.
     *
     * @param DOMElement $instruction XML element reprezentující instrukci ADD.
     * @param array<string, mixed> $variables Asociativní pole uchovávající proměnné, kde klíče jsou názvy proměnných a hodnoty jsou jejich aktuální hodnoty.
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $arg1Element = $instruction->getElementsByTagName('arg1')->item(0);
        if ($arg1Element === null) {
            exit(54);
        }
        $varName = $arg1Element->nodeValue;

        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);




        $result = $symb1Value + $symb2Value;


        $variables[$varName] = $result;
    }
    /**
     * @param mixed $symbArgNode The symbol argument node.
     * @param array<string, mixed> $variables asociativní pole uchovávající proměnné, kde klíče jsou názvy proměnných a hodnoty jsou jejich aktuální hodnoty..
     * 
     * @return mixed hodnota daného symbolu
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode === null) {
            return null;
        }

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $varoutput = $variables[$symbVarName];
            if (!is_numeric($varoutput)) {
                exit(53);
            }
            return $varoutput;
        } elseif ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'int') {
            $value = $symbArgNode->nodeValue;
            if (!is_numeric($value)) {
                exit(32);
            }
            return $value;
        } else {
            exit(53);
        }
    }
}

class SubInstruction
{
    /**
     * Provádí instrukci SUB kde dochází k odečtení dvou hodnot a uložení do proměnné
     *
     * @param DOMElement $instruction 
     * @param array<string, mixed> 
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);


        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);

        $result = $symb1Value - $symb2Value;


        $variables[$varName] = $result;
    }
    /**

     * @param mixed $symbArgNode 
     * @param array<string, mixed> $variables 
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode === null) {
            return null;
        }

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $varoutput = $variables[$symbVarName];
            if (!is_numeric($varoutput)) {
                exit(53);
            }
            return $varoutput;
        } elseif ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'int') {
            $value = $symbArgNode->nodeValue;
            if (!is_numeric($value)) {
                exit(32);
            }
            return $value;
        } else {
            exit(53);
        }
    }
}

class MulInstruction
{
    /**
     *Funkce provadějící instrukci MUL kde dochází k vynásobení dvou hodnot a uložení do proměnné
     * @param DOMElement $instruction 
     * @param array<string, mixed> $variables 
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if (!is_numeric($symb1Value) || !is_numeric($symb2Value)) {
            exit(53);
        }

        if ($symb2Value === 0) {
            exit(57);
        }



        $result = $symb1Value * $symb2Value;


        $variables[$varName] = $result;
    }
    /**
     * @param mixed $symbArgNode 
     * @param array<string, mixed> $variables 
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode === null) {
            return null;
        }

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $output = $variables[$symbVarName];
            if (!is_numeric($output)) {
                exit(53);
            }
            return $output;
        } elseif ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'int') {
            $value = $symbArgNode->nodeValue;
            if (!is_numeric($value)) {
                exit(32);
            }
            return $value;
        } else {
            exit(53);
        }
    }
}

class IdivInstruction
{
    /**
     * funkce provadějící dělení dvou hodnot
     *
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if (!is_numeric($symb1Value) || !is_numeric($symb2Value)) {
            exit(53);
        }

        if ($symb2Value === 0) {
            exit(57);
        }



        $result = intval($symb1Value / $symb2Value);


        $variables[$varName] = $result;
    }
    /**
     * @param mixed $symbArgNode 
     * @param array<string, mixed> $variables 
     * 
     * @return int The symbol value.
     */
    private function getSymbolValue($symbArgNode, $variables): ?int
    {
        if ($symbArgNode === null) {
            return null;
        }

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $output = $variables[$symbVarName];
            if (!is_numeric($output)) {
                exit(53);
            }
            return $output;
        } elseif ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'int') {
            $value = $symbArgNode->nodeValue;
            if (!is_numeric($value)) {
                exit(32);
            }
            return $value;
        } else {
            exit(53);
        }
    }
}

class AndInstruction
{
    /**
     * @param DOMElement $instruction 
     * @param array<string, mixed> $variables 
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);


        if ($symb1ArgNode->hasAttribute('type') && $symb1ArgNode->getAttribute('type') === 'var') {

            $symb1VarName = $symb1ArgNode->nodeValue;
            $symb1Value = $variables[$symb1VarName];
        } else {

            $symb1Value = $symb1ArgNode->nodeValue;
        }


        if ($symb2ArgNode->hasAttribute('type') && $symb2ArgNode->getAttribute('type') === 'var') {

            $symb2VarName = $symb2ArgNode->nodeValue;
            $symb2Value = $variables[$symb2VarName];
        } else {

            $symb2Value = $symb2ArgNode->nodeValue;
        }

        if ($symb1Value != 'true' && $symb1Value != 'false') {
            exit(53);
        }

        if ($symb2Value != 'true' && $symb2Value != 'false') {
            exit(53);
        }
        if ($symb1Value == 'true') {

            if ($symb2Value == 'true') {
                $result = 'true';
            } else {
                $result = 'false';
            }
        } else {
            $result = 'false';
        }

        $variables[$varName] = $result;
    }
}

class ConcatInstruction
{
    /**
     * @param DOMElement $instruction 
     * @param array<string, mixed> $variables 
     * 
     * @return void
     */
    public function execute($instruction, &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);


        if ($symb1ArgNode->hasAttribute('type') && $symb1ArgNode->getAttribute('type') === 'var') {

            $symb1VarName = $symb1ArgNode->nodeValue;
            $symb1Value = $variables[$symb1VarName];
        } else if ($symb2ArgNode->hasAttribute('type') && $symb2ArgNode->getAttribute('type') === 'string') {

            $symb1Value = $symb1ArgNode->nodeValue;
        } else {
            exit(53);
        }
        if (is_numeric($symb1Value)) {
            exit(53);
        } elseif ($symb1Value == "true" || $symb1Value == "false" || $symb1Value == "nil") {
            exit(53);
        }


        if ($symb2ArgNode->hasAttribute('type') && $symb2ArgNode->getAttribute('type') === 'var') {

            $symb2VarName = $symb2ArgNode->nodeValue;
            $symb2Value = $variables[$symb2VarName];
        } else if ($symb2ArgNode->hasAttribute('type') && $symb2ArgNode->getAttribute('type') === 'string') {

            $symb2Value = $symb2ArgNode->nodeValue;
        } else {
            exit(53);
        }
        if (is_numeric($symb2Value)) {
            exit(53);
        } elseif (($symb2Value == "true" || $symb2Value == "false" || $symb2Value == "nil")) {
            exit(53);
        }




        $concatenatedString = $symb1Value . $symb2Value;


        $variables[$varName] = $concatenatedString;
    }
}

class DPrintInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables 
     * 
     * @return string
     */
    public function execute(DOMElement $instruction, array &$variables): string
    {

        $symbArgNode = $instruction->getElementsByTagName('arg1')->item(0);
        $symbValue = $this->getSymbolValue($symbArgNode, $variables);


        return $symbValue;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class EqInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {

        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);


        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);

        if ($symb2Value != "nil"  && $symb1Value != "nil") {
            if (is_numeric($symb2Value) && (!is_numeric($symb1Value))) {
                exit(53);
            }

            if (is_numeric($symb1Value) && (!is_numeric($symb2Value))) {
                exit(53);
            }
            if (($symb1Value === "true" || $symb1Value === "false") && ($symb2Value != "true" && $symb2Value != "false")) {

                exit(53);
            }
            if (($symb2Value === "true" || $symb2Value === "false") && ($symb1Value != "true" && $symb1Value != "false")) {
                exit(53);
            }
        }



        $result = $symb1Value === $symb2Value;
        if ($symb1Value === $symb2Value) {
            $result = true;
        } else {
            $result = false;
        }


        $variables[$varName] = $result;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class JumpIfEqInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    public function execute($instruction, &$variables)
    {
        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);


        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if ($symb2Value != "nil"  && $symb1Value != "nil") {
            if (is_numeric($symb2Value) && (!is_numeric($symb1Value))) {
                exit(53);
            }

            if (is_numeric($symb1Value) && (!is_numeric($symb2Value))) {
                exit(53);
            }
            if (($symb1Value === "true" || $symb1Value === "false") && ($symb2Value != "true" && $symb2Value != "false")) {

                exit(53);
            }
            if (($symb2Value === "true" || $symb2Value === "false") && ($symb1Value != "true" && $symb1Value != "false")) {
                exit(53);
            }
        }

        if ($symb1Value == $symb2Value) {
            $result = true;
        } else {
            $result = false;
        }


        return $result;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class JumpIfNEqInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    public function execute($instruction, &$variables)
    {
        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if ($symb2Value != "nil"  && $symb1Value != "nil") {
            if (is_numeric($symb2Value) && (!is_numeric($symb1Value))) {
                exit(53);
            }

            if (is_numeric($symb1Value) && (!is_numeric($symb2Value))) {
                exit(53);
            }
            if (($symb1Value === "true" || $symb1Value === "false") && ($symb2Value != "true" && $symb2Value != "false")) {

                exit(53);
            }
            if (($symb2Value === "true" || $symb2Value === "false") && ($symb1Value != "true" && $symb1Value != "false")) {
                exit(53);
            }
        }

        if ($symb1Value == $symb2Value) {
            $result = false;
        } else {
            $result = true;
        }


        return $result;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class GetCharInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $stringArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $stringValue = $this->getSymbolValue($stringArgNode, $variables);

        $indexArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $indexValue = $this->getSymbolValue($indexArgNode, $variables);

        if (!is_numeric($indexValue)) {
            exit(53);
        }

        if (is_numeric($stringValue) || $stringValue === "true" || $stringValue === "false" || $stringValue === "nil") {
            exit(53);
        }

        if ($indexValue < 0 || $indexValue >= mb_strlen($stringValue)) {
            exit(58);
        }

        $char = mb_substr($stringValue, $indexValue, 1);


        $variables[$varName] = $char;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}


class GtInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {

        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if ($symb2Value === "nil"  || $symb1Value === "nil") {
            exit(53);
        }
        if (is_numeric($symb2Value) && (!is_numeric($symb1Value))) {
            exit(53);
        }

        if (is_numeric($symb1Value) && (!is_numeric($symb2Value))) {
            exit(53);
        }
        if (($symb1Value === "true" || $symb1Value === "false") && ($symb2Value != "true" && $symb2Value != "false")) {

            exit(53);
        }
        if (($symb2Value === "true" || $symb2Value === "false") && ($symb1Value != "true" && $symb1Value != "false")) {
            exit(53);
        }

        $result = $symb1Value > $symb2Value;

        $variables[$varName] = $result;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed The symbol value.
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class Int2CharInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $symbArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symbValue = $this->getSymbolValue($symbArgNode, $variables);

        if (!is_numeric($symbValue)) {
            exit(53);
        }

        $char = mb_chr($symbValue);

        if ($char == false) {
            exit(58);
        }

        $variables[$varName] = $char;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class LtInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {

        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);


        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);
        if ($symb2Value === "nil"  || $symb1Value === "nil") {
            exit(53);
        }
        if (is_numeric($symb2Value) && (!is_numeric($symb1Value))) {
            exit(53);
        }

        if (is_numeric($symb1Value) && (!is_numeric($symb2Value))) {
            exit(53);
        }
        if (($symb1Value === "true" || $symb1Value === "false") && ($symb2Value != "true" && $symb2Value != "false")) {

            exit(53);
        }
        if (($symb2Value === "true" || $symb2Value === "false") && ($symb1Value != "true" && $symb1Value != "false")) {
            exit(53);
        }

        $result = $symb1Value < $symb2Value;

        $variables[$varName] = $result;
    }
    /**

     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class NotInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $symbArgNode = $instruction->getElementsByTagName('arg2')->item(0);


        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $symbValue = $variables[$symbVarName];
        } else {

            $symbValue = $symbArgNode->nodeValue;
        }
        if ($symbValue != "true" && $symbValue != "false") {
            exit(53);
        }

        $result = ($symbValue === 'true') ? 'false' : 'true';


        $variables[$varName] = $result;
    }
}

class OrInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;


        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);


        if ($symb1ArgNode->hasAttribute('type') && $symb1ArgNode->getAttribute('type') === 'var') {

            $symb1VarName = $symb1ArgNode->nodeValue;
            $symb1Value = $variables[$symb1VarName];
        } else {

            $symb1Value = $symb1ArgNode->nodeValue;
        }


        if ($symb2ArgNode->hasAttribute('type') && $symb2ArgNode->getAttribute('type') === 'var') {

            $symb2VarName = $symb2ArgNode->nodeValue;
            $symb2Value = $variables[$symb2VarName];
        } else {

            $symb2Value = $symb2ArgNode->nodeValue;
        }

        if (is_numeric($symb1Value) || is_numeric($symb2Value)) {
            exit(53);
        }

        if ($symb1Value === 'nil' || $symb2Value === 'nil') {
            exit(53);
        }
        if (($symb1Value !== 'true' && $symb1Value !== 'false') || ($symb2Value !== 'true' && $symb2Value !== 'false')) {
            exit(53);
        }

        $result = 'true';
        if ($symb1Value === 'false' && $symb2Value === 'false') {
            $result = false;
        }

        $variables[$varName] = $result;
    }
}

class SetCharInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {

        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $indexArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $indexValue = $this->getSymbolValue($indexArgNode, $variables);

        $newCharArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $newCharValue = $this->getSymbolValue($newCharArgNode, $variables);

        $stringVarValue = $variables[$varName];

        if (!is_numeric($indexValue)) {
            exit(53);
        }
        if ($stringVarValue === 'true' || $stringVarValue === 'false' || is_numeric($stringVarValue) || $stringVarValue === 'nil') {
            exit(53);
        }
        if ($newCharValue === 'true' || $newCharValue === 'false' || is_numeric($newCharValue) || $newCharValue === 'nil') {
            exit(53);
        }

        if ($indexValue < 0 || $indexValue >= mb_strlen($stringVarValue)) {
            exit(58);
        }

        if (!is_string($newCharValue) || mb_strlen($newCharValue) !== 1) {
            exit(33);
        }

        $stringVarArray = preg_split('//u', $stringVarValue, -1, PREG_SPLIT_NO_EMPTY);
        $stringVarArray[$indexValue] = $newCharValue;
        $newStringValue = implode('', $stringVarArray);

        $variables[$varName] = $newStringValue;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class Stri2IntInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $symb1ArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symb1Value = $this->getSymbolValue($symb1ArgNode, $variables);

        $symb2ArgNode = $instruction->getElementsByTagName('arg3')->item(0);
        $symb2Value = $this->getSymbolValue($symb2ArgNode, $variables);

        if (!is_numeric($symb2Value)) {
            exit(53);
        }
        if ($symb1Value == 'true' || $symb1Value == 'false' || $symb1Value == 'nil' || $symb1Value == null) {
            exit(53);
        }
        if ($symb2Value == 'true' || $symb2Value == 'false' || is_numeric($symb1Value) || $symb2Value == 'nil') {
            exit(53);
        }
        if ($symb2Value < 0 || $symb2Value >= mb_strlen($symb1Value)) {
            exit(58);
        }

        $char = mb_substr($symb1Value, $symb2Value, 1);
        $ordinalValue = mb_ord($char);

        $variables[$varName] = $ordinalValue;
    }
    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            return $variables[$symbVarName];
        } else {

            return $symbArgNode->nodeValue;
        }
    }
}

class StrlenInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $varName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;
        $symbArgNode = $instruction->getElementsByTagName('arg2')->item(0);

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {

            $symbVarName = $symbArgNode->nodeValue;
            $symbValue = $variables[$symbVarName];
        } else {

            $symbValue = $symbArgNode->nodeValue;
        }
        if (is_numeric($symbValue) || $symbValue === 'true' || $symbValue === 'false' || $symbValue === 'nil' || $symbValue === null) {
            exit(53);
        }

        $strlen = strlen($symbValue);


        $variables[$varName] = $strlen;
    }
}

class TypeInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return void
     */
    public function execute(DOMElement $instruction, array &$variables): void
    {
        $type = '';

        $targetVarName = $instruction->getElementsByTagName('arg1')->item(0)->nodeValue;

        $symbolArgNode = $instruction->getElementsByTagName('arg2')->item(0);
        $symbolValue = null;
        if ($symbolArgNode->hasAttribute('type') && $symbolArgNode->getAttribute('type') === 'var') {
            $symbolVarName = $symbolArgNode->nodeValue;
            $symbolValue = $variables[$symbolVarName];
        } else {
            $symbolValue = null;
            $type = $symbolArgNode->getAttribute('type');
            if ($type === 'nil') {
                echo ($type);
            }
        }

        if ($symbolValue !== null) {
            $type = $this->getType($symbolValue);
        }

        $variables[$targetVarName] = $type;
    }

    /**
     * @param mixed $symbolValue
     * 
     * @return string
     */
    private function getType($symbolValue): string
    {
        if (is_null($symbolValue)) {
            return 'nil';
        } elseif (is_bool($symbolValue)) {
            return 'bool';
        } elseif (is_numeric($symbolValue)) {
            return 'int';
        } elseif (is_string($symbolValue)) {
            return 'string';
        } else {
            return '';
        }
    }
}

class ExitInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     */
    public function execute(DOMElement $instruction, array $variables): void
    {
        $symbArgNode = $instruction->getElementsByTagName('arg1')->item(0);
        $symbValue = $this->getSymbolValue($symbArgNode, $variables);

        if (!is_numeric($symbValue) || $symbValue < 0 || $symbValue > 9 || intval($symbValue) != $symbValue) {
            exit(57);
        }

        exit(intval($symbValue));
    }

    /**
     * @param mixed $symbArgNode
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    private function getSymbolValue($symbArgNode, $variables): mixed
    {
        if ($symbArgNode === null) {
            return null;
        }

        if ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'var') {
            $symbVarName = $symbArgNode->nodeValue;
            $retvalue = $variables[$symbVarName] ?? null;
            if (!is_numeric($retvalue)) {
                exit(53);
            }
            return intval($retvalue);
        } elseif ($symbArgNode->hasAttribute('type') && $symbArgNode->getAttribute('type') === 'int') {
            $value = $symbArgNode->nodeValue;
            if (!is_numeric($value)) {
                exit(32);
            }
            return intval($value);
        } else {
            exit(53);
        }
    }
}

class WriteInstruction
{
    /**
     * @param DOMElement $instruction
     * @param array<string, mixed> $variables
     * 
     * @return mixed
     */
    public function execute(DOMElement $instruction, array &$variables): mixed
    {

        $argNode = $instruction->getElementsByTagName('arg1')->item(0);
        if ($argNode === null) {
            exit(32);
        }

        if ($argNode->hasAttribute('type') && $argNode->getAttribute('type') === 'var') {

            $varName = $argNode->nodeValue;
            return $variables[$varName];
        } else {

            return $argNode->nodeValue;
        }
    }
}
