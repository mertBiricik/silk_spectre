from django.db import models
from django.utils import timezone

class Poll(models.Model):
    """Model representing a poll question for audience voting."""
    question = models.CharField(max_length=255)
    description = models.TextField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    is_active = models.BooleanField(default=False)
    start_time = models.DateTimeField(null=True, blank=True)
    end_time = models.DateTimeField(null=True, blank=True)
    
    def __str__(self):
        return self.question
    
    def is_open(self):
        """Check if the poll is currently open for voting."""
        now = timezone.now()
        if not self.is_active:
            return False
        if self.start_time and now < self.start_time:
            return False
        if self.end_time and now > self.end_time:
            return False
        return True
    
    def get_results(self):
        """Get voting results for this poll."""
        choices = self.choices.all()
        total_votes = sum(choice.votes for choice in choices)
        results = {
            'total_votes': total_votes,
            'choices': []
        }
        
        for choice in choices:
            percentage = (choice.votes / total_votes * 100) if total_votes > 0 else 0
            results['choices'].append({
                'id': choice.id,
                'text': choice.text,
                'votes': choice.votes,
                'percentage': round(percentage, 2)
            })
        
        return results

class Choice(models.Model):
    """Model representing a choice for a poll."""
    poll = models.ForeignKey(Poll, related_name='choices', on_delete=models.CASCADE)
    text = models.CharField(max_length=255)
    votes = models.IntegerField(default=0)
    narrative_path = models.TextField(blank=True, null=True, 
                                      help_text="Description of the narrative path if this choice wins")
    created_at = models.DateTimeField(auto_now_add=True)
    
    def __str__(self):
        return f"{self.poll.question} - {self.text}"
    
    class Meta:
        ordering = ['id']
