<div class="offcanvas offcanvas-start mobile-navigation" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-title">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="mobile-navigation-title">Menu keluarga</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body"><?php if (isset($component)) { $__componentOriginal9d69c11139dac45c50995da6fd1bec81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9d69c11139dac45c50995da6fd1bec81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.sidebar','data' => ['mobile' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9d69c11139dac45c50995da6fd1bec81)): ?>
<?php $attributes = $__attributesOriginal9d69c11139dac45c50995da6fd1bec81; ?>
<?php unset($__attributesOriginal9d69c11139dac45c50995da6fd1bec81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9d69c11139dac45c50995da6fd1bec81)): ?>
<?php $component = $__componentOriginal9d69c11139dac45c50995da6fd1bec81; ?>
<?php unset($__componentOriginal9d69c11139dac45c50995da6fd1bec81); ?>
<?php endif; ?></div>
</div>
<?php /**PATH /var/www/html/resources/views/components/navigation/mobile.blade.php ENDPATH**/ ?>