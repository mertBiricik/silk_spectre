from django.db import models

# Create your models here.

class Performance(models.Model):
    """Model representing a theatre performance."""
    title = models.CharField(max_length=255)
    date = models.DateField()
    time = models.TimeField()
    description = models.TextField(blank=True, null=True)
    is_active = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    def __str__(self):
        return f"{self.title} - {self.date} {self.time}"
    
    class Meta:
        ordering = ['-date', '-time']

class Story(models.Model):
    """Model representing a story line in the performance."""
    performance = models.ForeignKey(Performance, related_name='stories', on_delete=models.CASCADE)
    title = models.CharField(max_length=255)
    description = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)
    
    def __str__(self):
        return f"{self.performance.title} - {self.title}"
