import re
import sys
import io
import codecs


def print_xml_head():
    print("<?xml version=\"1.0\" encoding=\"UTF-8\"?>")
    print("<program language=\"IPPcode24\">")

def print_xml_out():
    print("</program>")
    sys.stdout = original_stdout
    output_content = output_buffer.getvalue()
    print(output_content)

def error_exit(error):
    sys.exit(error)

def change_char(string):
    string = string.replace('&', '&amp;')
    string = string.replace('<', '&lt;')
    string = string.replace('>', '&gt;')
    return string

def check_var(string):
    
    if re.match(r"^(GF|LF|TF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$", string):
        return change_char(string)
    else:
        return False

def check_symb(string):
    #print(string)
    #print("897")
    if check_var(string) is not False:
        return change_char(string)
    elif re.match(r"^string@(\\[0-9]{3}|[^#\s\\])*$", string):
        return change_char(string)
    elif re.match(r"^int@[-\+]?[0-9][0-9]*$", string):
        return string
    elif re.match(r"^nil@nil$", string):
        return string
    elif re.match(r"^bool@(true|false)$", string):
        return string
    else:
        return False

def check_type(string):
    
    if string in ["int", "bool", "string"]:
        return True
    else:
        return False

def check_label(string):
    
    if re.match(r"^[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$", string):
        return change_char(string)
    else:
        return False



def error_exit(error):
    sys.exit(error)


if len(sys.argv) == 2:
    if sys.argv[1] == "--help":
        print("The script witch reads code from stdin in IPPcode24 language, checks correctness of the code and returns XML representation of it to stdout.\n")
        print("Use:  --help for hint\n")
        print("\"python3.10 parse.py <input_file >output_file\" for running the script\n")
        print("Return values:    0  - correct function of script\n")
        print("                  10 - wrong combination of script's parameters\n")
        print("                  11 - error opening input file\n")
        print("                  99 - internal script's error\n")
        print("                  21 - missing header in input file\n")
        print("                  22 - wrong/unknown instructions used in input file\n")
        print("                  23 - lexical or parsing error\n")
        sys.exit(0)
    else:
        sys.exit(10)
elif len(sys.argv) == 1:
    
    output_buffer = io.StringIO()
    original_stdout = sys.stdout
    sys.stdout = output_buffer
else:
    
    sys.exit(10)


file = sys.stdin

if not file:
    error_exit(11)


instruction_cnt = 1

header_checked = 0

line = file.readline()
#print(line)
while line:
    #print(line)
    
    #print("b")
    if line.startswith("#"):
        line = file.readline()
        continue
    

    if "#" in line:
        line = line[:line.index("#")]

    line = line.strip()
    #line = bytes(line, "utf-8").decode("unicode_escape")
    #print(line)
    #line = line.encode('utf-8').decode('unicode_escape')
    #line = line.replace("\032", "\\032").replace("\010", "\\010").replace("\000", "\\000").replace("\001", "\\001").replace("\002", "\\002").replace("\003", "\\003").replace("\004", "\\004").replace("\005", "\\005").replace("\006", "\\006").replace("\007", "\\007").replace("\008", "\\008").replace("\009", "\\009").replace("\010", "\\010").replace("\011", "\\011").replace("\012", "\\012")
    #print(line)
    #print("a")
    #line = line.replace("\013", "\\013").replace("\014", "\\014").replace("\015", "\\015").replace("\016", "\\016").replace("\017", "\\017").replace("\018", "\\018").replace("\019", "\\019").replace("\020", "\\020").replace("\021", "\\021").replace("\022", "\\022").replace("\023", "\\023").replace("\024", "\\024").replace("\025", "\\025").replace("\026", "\\026").replace("\027", "\\027")
    #print(line)
    #line = line.replace("\028", "\\028").replace("\029", "\\029").replace("\030", "\\030").replace("\031", "\\031").replace("\035", "\\035").replace("\092", "\\092")
    line = line.replace("\092", "\\092").replace("\035", "\\035").replace("\032", "\\032").replace("\031", "\\031").replace("\030", "\\030").replace("\029", "\\029").replace("\028", "\\028").replace("\027", "\\027").replace("\026", "\\026").replace("\025", "\\025").replace("\024", "\\024").replace("\023", "\\023").replace("\022", "\\022").replace("\021", "\\021").replace("\020", "\\020").replace("\019", "\\019").replace("\018", "\\018").replace("\017", "\\017").replace("\016", "\\016").replace("\015", "\\015").replace("\014", "\\014").replace("\013", "\\013").replace("\012", "\\012").replace("\011", "\\011").replace("\010", "\\010").replace("\009", "\\009").replace("\008", "\\008").replace("\007", "\\007").replace("\006", "\\006").replace("\005", "\\005").replace("\004", "\\004").replace("\003", "\\003").replace("\002", "\\002").replace("\001", "\\001").replace("\000", "\\000")
    line = line.split()
    #print(line)
    line = list(filter(None, line))
    #print("j")
    #print(line)
    

    if not line:
        line = file.readline()
        continue
 
    line[0] = line[0].upper()
 
    if header_checked == 0:
        if line[0] == ".IPPCODE24":
            header_checked = 1
            if len(line) > 1:
                error_exit(21)
            print_xml_head()
            line = file.readline()
            continue
        else:
            error_exit(21)
    if header_checked == 1:
        if line[0] == ".IPPCODE24":
            error_exit(23)
    #print("a")
    #print(line)
    if line[0] in ["INT2CHAR", "STRLEN", "NOT", "TYPE", "MOVE"]:
       #print(line)
        if len(line) != 3:
            error_exit(23)
        
        var = check_var(line[1])
        if not var:
            error_exit(23)
        #print(line[2])
        #line[2] = line[2].replace("\\", "\\")
        #print(line[2])
        symb = check_symb(line[2])
        if not symb:
            error_exit(23)
        #line[2] = line[2].replace("\7", "\\")
        #print(line[2])
        
        
        symb = symb.split("@")
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"var\">{var}</arg1>")
        if symb[0] in ["LF", "GF", "TF"]:
            print(f"\t\t<arg2 type=\"var\">{symb[0]}@{symb[1]}</arg2>")
        else:
            print(f"\t\t<arg2 type=\"{symb[0]}\">{symb[1]}</arg2>")
        print("\t</instruction>")

    elif line[0] in ["RETURN", "PUSHFRAME", "POPFRAME", "BREAK", "CREATEFRAME"]:
        if len(line) != 1:
            error_exit(23)
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print("\t</instruction>")

    elif line[0] in ["POPS", "DEFVAR"]:
        #print("as")
        if len(line) != 2:
            error_exit(23)
        var = check_var(line[1])
        if not var:
            error_exit(23)
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"var\">{var}</arg1>")
        print("\t</instruction>")

    
    elif line[0] in ["CALL", "JUMP", "LABEL"]:
        if len(line) != 2:
            error_exit(23)
        label = check_label(line[1])
        if not label:
            error_exit(23)
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"label\">{label}</arg1>")
        print("\t</instruction>")

    elif line[0] in ["PUSHS", "WRITE", "EXIT", "DPRINT"]:
        #print(len(line))
        if len(line) != 2:
            error_exit(23)
            #print(line[1])
        symb = check_symb(line[1])
        if not symb:
            error_exit(23)
        ######################################
        symb = symb.split("@")
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        if symb[0] in ["LF", "GF", "TF"]:
            print(f"\t\t<arg1 type=\"var\">{symb[0]}@{symb[1]}</arg1>")
        else:
            
            print(f"\t\t<arg1 type=\"{symb[0]}\">{symb[1]}</arg1>")
        print("\t</instruction>")

    elif line[0] in ["ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR"]:
        if len(line) != 4:
            error_exit(23)
        var = check_var(line[1])
        if not var:
            error_exit(23)
        symb1 = check_symb(line[2])
        if not symb1:
            error_exit(23)
        symb2 = check_symb(line[3])
        if not symb2:
            error_exit(23)
        symb1 = symb1.split("@")
        symb2 = symb2.split("@")
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"var\">{var}</arg1>")
        if symb1[0] in ["LF", "GF", "TF"]:
            print(f"\t\t<arg2 type=\"var\">{symb1[0]}@{symb1[1]}</arg2>")
        else:
            print(f"\t\t<arg2 type=\"{symb1[0]}\">{symb1[1]}</arg2>")
        if symb2[0] in ["LF", "GF", "TF"]:
            print(f"\t\t<arg3 type=\"var\">{symb2[0]}@{symb2[1]}</arg3>")
        else:
            print(f"\t\t<arg3 type=\"{symb2[0]}\">{symb2[1]}</arg3>")
        print("\t</instruction>")

    elif line[0] == "READ":
        if len(line) != 3:
            error_exit(23)
        var = check_var(line[1])
        if not var:
            error_exit(23)
        type = check_type(line[2])
        if not type:
            error_exit(23)
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"var\">{var}</arg1>")
        print(f"\t\t<arg2 type=\"type\">{line[2]}</arg2>")
        print("\t</instruction>")

    elif line[0] == "READ":
        if len(line) != 3:
            error_exit(23)
        var = check_var(line[1])
        if not var:
            error_exit(23)
        type = check_type(line[2])
        if not type:
            error_exit(23)
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"var\">{var}</arg1>")
        print(f"\t\t<arg2 type=\"type\">{line[2]}</arg2>")
        print("\t</instruction>")
        line = file.readline()

    elif line[0] == "JUMPIFEQ" or line[0] == "JUMPIFNEQ":
        if len(line) != 4:
            error_exit(23)
        label = check_label(line[1])
        if not label:
            error_exit(23)
        symb1 = check_symb(line[2])
        if not symb1:
            error_exit(23)
        symb2 = check_symb(line[3])
        if not symb2:
            error_exit(23)
        symb1 = symb1.split("@")
        symb2 = symb2.split("@")
        print(f"\t<instruction order=\"{instruction_cnt}\" opcode=\"{line[0]}\">")
        instruction_cnt += 1
        print(f"\t\t<arg1 type=\"label\">{label}</arg1>")
        if symb1[0] == "LF" or symb1[0] == "GF" or symb1[0] == "TF":
            print(f"\t\t<arg2 type=\"var\">{symb1[0]}@{symb1[1]}</arg2>")
        else:
            print(f"\t\t<arg2 type=\"{symb1[0]}\">{symb1[1]}</arg2>")
        if symb2[0] == "LF" or symb2[0] == "GF" or symb2[0] == "TF":
            print(f"\t\t<arg3 type=\"var\">{symb2[0]}@{symb2[1]}</arg3>")
        else:
            print(f"\t\t<arg3 type=\"{symb2[0]}\">{symb2[1]}</arg3>")
        print("\t</instruction>")
    
    else:
        error_exit(22)
        #print("ad")

    #print("f")
    #print(line)
    #print("o")
    line = file.readline()
    #print("s")
   # print(line)
    #print("a")
    


print_xml_out()

file.close()
