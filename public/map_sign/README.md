# jQuery图片标注组件（jquery.picsign）


> **在线演示：http://artlessbruin.gitee.io/picsign/** 

## 1. 组件依赖
**jquery**
```html
<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
```
**bootstrap**
```html
<link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
```
**layer**
```html
<link href="https://cdn.bootcss.com/layer/3.1.0/theme/default/layer.css" rel="stylesheet">
<script src="https://cdn.bootcss.com/layer/3.1.0/layer.js"></script>
```
**webui-popover**
```html
<link href="https://cdn.bootcss.com/webui-popover/2.1.15/jquery.webui-popover.min.css" rel="stylesheet">
<script src="https://cdn.bootcss.com/webui-popover/2.1.15/jquery.webui-popover.min.js"></script>
```
## 2. 引用组件文件
```html
<link href="css/jquery.picsign.css" rel="stylesheet" />
<script src="js/jquery.picsign.js"></script>
```
## 3. 使用
### 在页面中加入一个DIV
```html
<div id="picsign"></div>
```
### 初始化组件
```javascript
$("#picsign").picsign(option);
//option为组件参数，详细说明见option参数说明
```
### option参数说明
```javascript
var option={
    picurl: null,//图片地址
    signdata: [],//初始数据,详细说明参见基本数据格式
    editable: {//是否可编辑(默认可编辑 设置为false则禁用所有编辑)
        add: true,//是否可添加
        update: true,//是否可修改
        del: true,//是否可删除
        move: true//是否可移动
    },
    signclass: 'signdot',//标注点样式
    popwidth: 400,//标注内容显示窗口宽
    popheight: 247,//标注内容显示窗口高
    inputwidth: 400,//标注内容编辑窗口宽
    inputheight: 247,//标注内容编辑窗口高
    beforeadd: function (data) {//在添加保存前执行的方法，返回false阻断添加
    },
    onadd: function (data) {//添加完成执行的方法
    },
    beforeupdate: function (data) {//在修改保存前执行的方法，返回false阻断修改
    },
    onupdate: function (data) {//修改完成执行的方法
    },
    beforedel: function (data) {//在删除保存前执行的方法，返回false阻断删除
    },
    ondel: function (data) {//删除完成执行的方法
    }
};
```
### 基本数据格式
```
[{
    left:'50%',
    top:'50%',
    msg:'这是标注信息',
    signid:'这是标注唯一标识符，用户无需赋值，与组件逻辑相关，请不要使用此关键字'
}]
```
- 用户添加的数据中必须包含`left`、`top`、`msg`属性
- 用户可自行扩展其他属性
- **特殊说明**：请不要添加和使用`signid`关键字

### 方法调用
```javascript
$("#picsign").picsign('functionName',parameter);
//functionName为方法名称，parameter为方法参数，详细说明参见方法说明
```
### 方法说明
**获取标注数据**
> 方法名称：**`getData`**   
> 参数：无

```javascript
$("#picsign").picsign('getData');
```
**添加标注数据**
> 方法名称：**`addSign`**   
> 参数：基本数据Json,是否触发事件（默认为true）

```javascript
$("#div_picsign").picsign("addSign",
    [{ left: '50%', top: '10%', msg: "123"},
    { left: '80%', top: '10%', msg: "456"}],
	true
)
```
**切换标注显示状态**
> 方法名称：**`toggle`**   
> 参数：无

```javascript
$("#div_picsign").picsign("toggle")
```
**组件销毁**
> 方法名称：**`destroy`**   
> 参数：无

```javascript
$("#div_picsign").picsign("destroy")
```