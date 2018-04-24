
function awesomplete_name(name_element,code_element,get_name_url){
    $.get(get_name_url,{},function(data){
        var name=[];
        console.log(data);
        for(var i=0;i<data.length;i++){
            name[i] = data[i]['name'];
        }
        console.log(name);
        var input = document.getElementById(name_element);
        console.log(input);
        new Awesomplete(input, {
            minChars: 1,
            list: name,
        });
    });
    document.getElementById(name_element).onkeyup = function(event){
        if(event.keyCode==13){
             var name=$("#"+name_element).val();
             var namestrs=name.split('/');
             $("#"+name_element).val(namestrs[0]);
             $("#"+code_element).val(namestrs[1]);
        }
    };
}

function awesomplete_good_info(name_element,code_element,price_element,get_name_url){
    $.get(get_name_url,{},function(data){
        var name=[];
        var price=[];
        console.log(data);
        for(var i=0;i<data.length;i++){
            name[i] = data[i]['name'];
            price[i] = data[i]['price'];
        }
        console.log(name);
        var input = document.getElementById(name_element);
        console.log(input);
        new Awesomplete(input, {
            minChars: 1,
            list: name,
        });
    });
    document.getElementById(name_element).onkeyup = function(event){
        if(event.keyCode==13){
             var name=$("#"+name_element).val();
             var namestrs=name.split('/');
             $("#"+name_element).val(namestrs[0]);
             $("#"+code_element).val(namestrs[1]);
             $("#"+price_element).val(namestrs[2]);
        }
    };
}