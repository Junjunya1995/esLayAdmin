###  注意
此处用了 think-rom 
并且开启了 模型后缀
此处问题来了 tp-orm的模型后缀是根据文件加名字设置的
所以模型后缀的名字要与文件夹相同

可以在配置文件的
class_suffix => false

推荐默认不开启后缀

