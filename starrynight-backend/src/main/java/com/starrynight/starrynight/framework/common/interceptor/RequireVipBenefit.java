package com.starrynight.starrynight.framework.common.interceptor;

import java.lang.annotation.*;

@Target(ElementType.METHOD)
@Retention(RetentionPolicy.RUNTIME)
@Documented
public @interface RequireVipBenefit {

    String value();

    String message() default "";
}
