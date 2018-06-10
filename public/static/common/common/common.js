/**
 * Created by static7<static7@qq.com> on 2017-07-13.
 */
var loading,table_reload,pop_up;
layui.use(['layer', 'form', 'table'], function () {
    layui.$(document).ajaxStart(function () {
        loading = layui.layer.load(2);
    }).ajaxStop(function () {
        layui.layer.close(loading);
    });
    //全选
    layui.form.on('checkbox(all_checkbox)', function (data) {
        var child = layui.jquery(data.elem).parents('table').find('tbody input[type="checkbox"]');
        child.each(function (index, item) {
            item.checked = data.elem.checked;
        });
        layui.form.render('checkbox');
    });
    //ajax post submit请求
    layui.$('.ajax-post').on('click', function () {
        var checkStatus,Url,ids='',that=this;
        checkStatus = layui.table.checkStatus('ids');
        Url=layui.$(this).attr('href') || layui.$(this).attr('url');
        if(checkStatus.data.length===0){
            return layui.layer.msg('请选择数据！');
        }
        layui.$.each(checkStatus.data,function (i,e) {
            ids+="&ids[]="+e.id;
        })
        var encode=encodeURI(ids.substr(1));
        layui. layer.confirm('确认要执行该操作吗?',function (index) {
            layui.$(that).prop('disabled', true);
            layui.$.post(Url, encode).success(function (data) {
                layui.layer.close(index);
                layui.$(that).prop('disabled', false);
                if (data.code !== 1) {
                    return alert_msg(data.msg, 0)
                }
                alert_msg(data.msg, 1);
                setTimeout(function () {
                    table_reload.reload();
                }, 1500);
            });
        })
        return false;
    });

    //ajax get submit请求
    layui.$('.ajax-get').on('click', function () {
        var checkStatus,Url,ids='',that=this;
        checkStatus = layui.table.checkStatus('ids');
        Url=layui.$(this).attr('href') || layui.$(this).attr('url');
        if(checkStatus.data.length===0){
            return layui.layer.msg('请选择数据！');
        }
        layui.$.each(checkStatus.data,function (i,e) {
            ids+="&ids[]="+e.id;
        })
        var encode=encodeURI(ids.substr(1));
        layui.layer.confirm('确认要执行该操作吗?',function (index) {
            layui.$(that).prop('disabled', true);
            layui.$.get(Url, encode).success(function (data) {
                layui.layer.close(index);
                layui.$(that).prop('disabled', false);
                if (data.code !== 1) {
                    return alert_msg(data.msg, 0)
                }
                alert_msg(data.msg, 1);
                setTimeout(function () {
                    table_reload.reload();
                }, 1500);
            });
        })
        return false;
    });
    //特殊 ajax提交
    layui.$('.special').on('click', function () {
        var target;
        if (layui.$(this).hasClass('confirm')) {
            if (!confirm('确认要执行该操作吗?')) {
                return false;
            }
        }
        if ((target = layui.$(this).attr('href')) || (target = layui.$(this).attr('url'))) {
            layui.$.get(target).success(function (data) {
                status_load(data);
            });
        }
        return false;
    });

    //加载特效
    layui.$('.a_load').on('click',function () {
        layui.layer.load(2);
        window.location.href = layui.$(this).attr('href');
    })

    // 通用返回
    layui.$('.retreat').on('click', function () {
        history.back(-1);
        return false;
    });
});

//状态加载
function status_load(data, that) {
    layui.use(['jquery'], function () {
        if (data.code == 1) {
            (data.url !== null && data.url !== undefined) ? alert_msg(data.msg + ' 页面即将自动跳转~', 1) : alert_msg(data.msg, 1);
            setTimeout(function () {
                layui.$(that).prop('disabled', false);
                if (data.url !== null && data.url !== undefined) {
                    location.href = data.url;
                }
            }, 1500);
        } else {
            alert_msg(data.msg, 0);
            setTimeout(function () {
                layui.$(that).prop('disabled', false);
            }, 1500);
        }
    })
}

//layer通用提示框
function alert_msg(text, icon) {
    layui.use(['layer'], function () {
        text = text || '提交成功，系统未返回信息';
        icon = icon || 0;
        layui.layer.msg(text, {
            icon: icon,
            offset: 70,
            shift: 0
        });
    });
}

//导航高亮
function UrlHighlight(url) {
    layui.use('jquery', function () {
        layui.$('.highlight').find('a[data-url="' + url + '"]').parent().addClass('layui-this');
    });
}


//基础对象检测
function setChoose(name, value) {
    layui.use(['jquery','form'], function () {
        var first = name.substr(0, 1), input, i = 0, val;
        if (value === "")
            return '';
        if ("#" === first || "." === first) {
            input = layui.$(name);
        } else {
            input = layui.$("[name='" + name + "']");
        }

        if (input.eq(0).is(":radio")) { //单选按钮
            input.filter("[value='" + value + "']").each(function () {
                this.checked = true;
            });
        } else if (input.eq(0).is(":checkbox")) { //复选框
            if (!layui.$.isArray(value)) {
                val = new Array();
                val[0] = value;
            } else {
                val = value;
            }
            for (i = 0, len = val.length; i < len; i++) {
                input.filter("[value='" + val[i] + "']").each(function () {
                    this.checked = true;
                });
            }
        } else {  //其他表单选项直接设置值
            input.val(value);
        }
        layui.form.render();
    });
};

/**
 *拼接url
 */
function createURL(url, param) {//链接和参数
    if (!param){
        return url;
    }
    var link=url + "?";
    layui.use('jquery', function () {
        layui.$.each(param, function (item, key) {
             link +=item + "=" +key+'&';
        })
    });
    return link.substr(0,(link.length-1));
}